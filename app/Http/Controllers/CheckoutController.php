<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\OrderPaymentService;
use App\Services\OrderPricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Throwable;

class CheckoutController extends Controller
{
    private function getCart(): Cart
    {
        if (auth()->check()) {
            return Cart::firstOrCreate(['user_id' => auth()->id()]);
        }

        return Cart::firstOrCreate(['session_id' => session()->getId()]);
    }

    public function show(OrderPricingService $pricing): Response|RedirectResponse
    {
        $items = $this->getCart()->items()->with(['variant.product.images'])->get();

        if ($items->isEmpty()) {
            return redirect()->route('cart.show');
        }

        $subtotal = (float) $items->sum(fn ($i) => $i->variant->final_price * $i->quantity);
        $user = auth()->user();

        $quote = $pricing->quote($subtotal, [
            'address' => $user?->shipping_address,
            'city' => $user?->shipping_city,
            'state' => $user?->shipping_state ?? 'Lagos',
        ]);

        return Inertia::render('Store/Checkout', [
            'cartItems' => $items->map(fn ($item) => [
                'product_name' => $item->variant->product->name,
                'variant_description' => $item->variant->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->variant->final_price,
                'image' => $item->variant->product->images->first()
                    ? asset('storage/'.$item->variant->product->images->first()->image_path)
                    : null,
            ]),
            'quote' => $quote,
            'states' => config('store.nigeria_states'),
            'user' => $user,
            'savedCard' => $user && $user->paystack_auth_code ? [
                'last_four' => $user->card_last_four,
                'brand' => $user->card_brand,
            ] : null,
        ]);
    }

    public function initiate(Request $request, OrderPricingService $pricing, OrderPaymentService $payments)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => ['required', 'string', 'max:30', 'regex:/^\+?[0-9][0-9\s\-()]{6,}$/'],
            'shipping_address' => 'required|string|max:500',
            'shipping_city' => 'required|string|max:120',
            'shipping_state' => ['required', 'string', Rule::in(config('store.nigeria_states'))],
            'notes' => 'nullable|string|max:1000',
            'use_saved_card' => 'nullable|boolean',
            'save_card' => 'nullable|boolean',
        ], [
            'customer_phone.required' => 'Please enter a phone number so the courier can reach you.',
            'customer_phone.regex' => 'Please enter a valid phone number.',
        ]);

        $cart = $this->getCart();
        $items = $cart->items()->with(['variant.product'])->get();

        if ($items->isEmpty()) {
            return back()->withErrors(['cart' => 'Your bag is empty.']);
        }

        foreach ($items as $item) {
            $variant = $item->variant;

            if (! $variant || ! $variant->is_active || ! $variant->product?->is_active) {
                return back()->withErrors(['cart' => 'One or more items in your bag is no longer available. Please update your bag.']);
            }

            if ($variant->stock_quantity < $item->quantity) {
                $size = $variant->size ? " ({$variant->size})" : '';

                return back()->withErrors(['cart' => "Sorry, '{$variant->product->name}{$size}' only has {$variant->stock_quantity} left in stock. Please update your bag."]);
            }
        }

        $lock = Cache::lock('checkout_lock_'.$cart->id, 30);

        if (! $lock->get()) {
            return back()->withErrors(['checkout' => 'Your payment is already being set up. Please wait a moment.']);
        }

        try {
            $paystackKey = (string) config('services.paystack.secret_key');

            if ($paystackKey === '') {
                Log::error('Checkout blocked: PAYSTACK_SECRET_KEY is not configured.');

                return back()->withErrors(['payment' => 'Payment is temporarily unavailable. Please try again shortly.']);
            }

            $subtotal = (float) $items->sum(fn ($i) => $i->variant->final_price * $i->quantity);
            $quote = $pricing->quote($subtotal, [
                'address' => $validated['shipping_address'],
                'city' => $validated['shipping_city'],
                'state' => $validated['shipping_state'],
            ]);

            $user = auth()->user();
            $saveCard = $user && $request->boolean('save_card');

            $order = $this->findMatchingUnpaidOrder($cart, $validated, $items, $quote);
            $isNewOrder = ! $order;

            if ($order) {
                $order->update(['save_card' => $saveCard, 'notes' => $validated['notes'] ?? null]);
            } else {
                $order = $this->createOrder($cart, $validated, $items, $quote, $saveCard);
            }

            session(['recent_order_id' => $order->id]);

            $reference = $order->newPaymentReference();
            $amountInKobo = (int) round($quote['total'] * 100);

            if ($request->boolean('use_saved_card') && $user && $user->paystack_auth_code) {
                $charged = $this->chargeSavedCard($order, $items, $user->email, $user->paystack_auth_code, $amountInKobo, $reference);

                if ($charged) {
                    $payments->markPaid($order, $charged);

                    return redirect()->to($order->confirmationUrl());
                }

                // That attempt used up its reference; the redirect below takes a new one.
                $reference = $order->newPaymentReference();
            }

            try {
                $response = Http::withToken($paystackKey)
                    ->timeout(20)
                    ->post('https://api.paystack.co/transaction/initialize', [
                        'email' => $validated['customer_email'],
                        'amount' => $amountInKobo,
                        'currency' => 'NGN',
                        'reference' => $reference,
                        'channels' => ['card', 'bank', 'bank_transfer', 'ussd', 'qr'],
                        'metadata' => $this->buildMetadata($order, $items),
                        'callback_url' => route('checkout.callback'),
                    ]);
            } catch (Throwable $e) {
                Log::error("Paystack could not be reached for order {$order->order_number}: {$e->getMessage()}");
                $response = null;
            }

            if (! $response?->successful() || ! $response->json('data.authorization_url')) {
                Log::error("Paystack transaction/initialize failed for order {$order->order_number}", [
                    'http_status' => $response?->status(),
                    'response' => $response?->json() ?? $response?->body(),
                ]);

                // An older payment page for an existing order may still be open and payable.
                if ($isNewOrder) {
                    $order->delete();
                }

                return back()->withErrors(['payment' => 'We could not start your payment. You have not been charged. Please try again.']);
            }

            return Inertia::location($response->json('data.authorization_url'));
        } finally {
            $lock->release();
        }
    }

    public function callback(Request $request, OrderPaymentService $payments): RedirectResponse
    {
        $reference = (string) $request->query('reference', $request->query('trxref', ''));

        if (! preg_match('/^[A-Za-z0-9.=\-]+$/', $reference)) {
            return redirect()->route('home');
        }

        $order = Order::findByPaymentReference($reference);

        if (! $order) {
            return redirect()->route('home');
        }

        try {
            $response = Http::withToken((string) config('services.paystack.secret_key'))
                ->timeout(20)
                ->get("https://api.paystack.co/transaction/verify/{$reference}");
        } catch (Throwable $e) {
            Log::error("Paystack verification could not be reached for order {$order->order_number}: {$e->getMessage()}");
            $response = null;
        }

        if ($response?->successful() && is_array($response->json('data'))) {
            $payments->markPaid($order, $response->json('data'));
        }

        return redirect()->to($order->confirmationUrl());
    }

    public function calculateShippingRate(Request $request, OrderPricingService $pricing): JsonResponse
    {
        $validated = $request->validate([
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:120',
            'state' => ['required', 'string', Rule::in(config('store.nigeria_states'))],
        ]);

        $items = $this->existingCartItems();
        $subtotal = (float) $items->sum(fn ($i) => $i->variant->final_price * $i->quantity);

        return response()->json(['success' => true] + $pricing->quote($subtotal, $validated));
    }

    public function confirmation(Request $request, string $orderNumber): Response
    {
        $order = Order::with('items')->where('order_number', $orderNumber)->firstOrFail();

        $isOwner = auth()->check() && (int) auth()->id() === (int) $order->user_id;
        $isRecentSession = session('recent_order_id') === $order->id;
        $isAdmin = auth()->check() && auth()->user()->isAdmin();

        abort_unless($request->hasValidSignature() || $isOwner || $isRecentSession || $isAdmin, HttpResponse::HTTP_FORBIDDEN);

        return Inertia::render('Store/OrderConfirmation', [
            'order' => [
                'order_number' => $order->order_number,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'subtotal' => $order->subtotal,
                'total_amount' => $order->total_amount,
                'vat_amount' => $order->vat_amount,
                'vat_inclusive' => $order->vat_inclusive,
                'free_shipping' => $order->free_shipping,
                'customer_name' => $order->customer_name,
                'customer_email' => $order->customer_email,
                'shipping_city' => $order->shipping_city,
                'shipping_state' => $order->shipping_state,
                'shipping_address' => $order->shipping_address,
                'shipping_fee' => $order->shipping_fee,
                'routely_tracking_number' => $order->routely_tracking_number,
                'routely_status' => $order->routely_status,
                'items' => $order->items->map(fn ($i) => [
                    'product_name' => $i->product_name,
                    'variant_description' => $i->variant_description,
                    'quantity' => $i->quantity,
                    'unit_price' => $i->unit_price,
                    'total_price' => $i->total_price,
                ]),
            ],
        ]);
    }

    /**
     * Reuse an order only if the customer retried the exact same purchase.
     * Anything changed (size, address, total) must become a new order.
     *
     * @param  array<string, mixed>  $details
     * @param  Collection<int, mixed>  $items
     * @param  array<string, mixed>  $quote
     */
    private function findMatchingUnpaidOrder(Cart $cart, array $details, Collection $items, array $quote): ?Order
    {
        $order = Order::with('items')
            ->where('cart_id', $cart->id)
            ->where('payment_status', 'unpaid')
            ->where('created_at', '>=', now()->subMinutes(30))
            ->where('customer_name', $details['customer_name'])
            ->where('customer_email', $details['customer_email'])
            ->where('customer_phone', $details['customer_phone'])
            ->where('shipping_address', $details['shipping_address'])
            ->where('shipping_city', $details['shipping_city'])
            ->where('shipping_state', $details['shipping_state'])
            ->latest('id')
            ->first();

        if (! $order || (int) round((float) $order->total_amount * 100) !== (int) round($quote['total'] * 100)) {
            return null;
        }

        $wanted = $items->mapWithKeys(fn ($i) => [$i->product_variant_id => (int) $i->quantity])->sortKeys()->all();
        $existing = $order->items->mapWithKeys(fn ($i) => [$i->product_variant_id => (int) $i->quantity])->sortKeys()->all();

        return $wanted === $existing ? $order : null;
    }

    /**
     * @param  array<string, mixed>  $details
     * @param  Collection<int, mixed>  $items
     * @param  array<string, mixed>  $quote
     */
    private function createOrder(Cart $cart, array $details, Collection $items, array $quote, bool $saveCard): Order
    {
        return DB::transaction(function () use ($cart, $details, $items, $quote, $saveCard) {
            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'user_id' => auth()->id(),
                'cart_id' => $cart->id,
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'subtotal' => $quote['subtotal'],
                'shipping_fee' => $quote['shipping_fee'],
                'routely_fee' => $quote['courier_fee'],
                'free_shipping' => $quote['free_shipping'],
                'vat_amount' => $quote['vat_amount'],
                'vat_inclusive' => $quote['vat_inclusive'],
                'total_amount' => $quote['total'],
                'customer_name' => $details['customer_name'],
                'customer_email' => $details['customer_email'],
                'customer_phone' => $details['customer_phone'],
                'shipping_address' => $details['shipping_address'],
                'shipping_city' => $details['shipping_city'],
                'shipping_state' => $details['shipping_state'],
                'notes' => $details['notes'] ?? null,
                'save_card' => $saveCard,
            ]);

            foreach ($items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $item->variant->id,
                    'product_name' => $item->variant->product->name,
                    'variant_description' => $item->variant->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->variant->final_price,
                    'total_price' => $item->variant->final_price * $item->quantity,
                ]);
            }

            return $order;
        });
    }

    /**
     * @param  Collection<int, mixed>  $items
     * @return array<string, mixed>|null The transaction data when the charge succeeded.
     */
    private function chargeSavedCard(Order $order, Collection $items, string $email, string $authorizationCode, int $amountInKobo, string $reference): ?array
    {
        try {
            $response = Http::withToken((string) config('services.paystack.secret_key'))
                ->timeout(30)
                ->post('https://api.paystack.co/transaction/charge_authorization', [
                    'email' => $email,
                    'amount' => $amountInKobo,
                    'currency' => 'NGN',
                    'authorization_code' => $authorizationCode,
                    'reference' => $reference,
                    'metadata' => $this->buildMetadata($order, $items),
                ]);
        } catch (Throwable $e) {
            Log::warning("Saved-card charge could not be reached for order {$order->order_number}: {$e->getMessage()}");

            return null;
        }

        if ($response->successful() && $response->json('data.status') === 'success') {
            return $response->json('data');
        }

        Log::warning("Saved-card charge not completed for order {$order->order_number}", [
            'http_status' => $response->status(),
            'gateway_status' => $response->json('data.status'),
            'message' => $response->json('message'),
        ]);

        return null;
    }

    /**
     * @param  Collection<int, mixed>  $items
     * @return array<string, mixed>
     */
    private function buildMetadata(Order $order, Collection $items): array
    {
        return [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'custom_fields' => $items->map(fn ($item) => [
                'display_name' => $item->variant->product->name,
                'variable_name' => 'item_'.$item->variant->id,
                'value' => $item->variant->description
                    ? $item->variant->description.' × '.$item->quantity
                    : 'Qty: '.$item->quantity,
            ])->values()->all(),
        ];
    }

    /**
     * The visitor's cart items, without creating a cart for someone who has none.
     *
     * @return Collection<int, mixed>
     */
    private function existingCartItems(): Collection
    {
        $cart = auth()->check()
            ? Cart::where('user_id', auth()->id())->first()
            : Cart::where('session_id', session()->getId())->first();

        return $cart ? $cart->items()->with(['variant.product'])->get() : collect();
    }
}
