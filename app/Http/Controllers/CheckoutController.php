<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\StoreSetting;
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
            'countries' => $this->countryOptions(),
            'user' => $user,
            'savedCard' => $user && $user->paystack_auth_code ? [
                'last_four' => $user->card_last_four,
                'brand' => $user->card_brand,
            ] : null,
        ]);
    }

    /**
     * Countries for the checkout and cart pickers: Nigeria first, then any the
     * store has switched on. Fees and rates stay on the server.
     *
     * @return list<array{code: string, name: string, region_label: string, region_required: bool, postcode_label: string}>
     */
    public static function countryOptions(): array
    {
        $nigeria = ['code' => 'NG'] + config('store.countries.NG');
        $options = [];

        foreach ([$nigeria, ...array_values(StoreSetting::current()->internationalCountries())] as $country) {
            $options[] = collect($country)->only(['code', 'name', 'region_label', 'region_required', 'postcode_label'])->all();
        }

        return $options;
    }

    public function initiate(Request $request, OrderPricingService $pricing, OrderPaymentService $payments)
    {
        $validated = $this->validateAddress($request, [
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => ['required', 'string', 'max:30', 'regex:/^\+?[0-9][0-9\s\-()]{6,}$/'],
            'shipping_address' => 'required|string|max:500',
            'shipping_city' => 'required|string|max:120',
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
                'country' => $validated['shipping_country'],
            ]);

            $user = auth()->user();
            $saveCard = $user && $request->boolean('save_card');

            $isNewOrder = false;

            // Checking stock and setting it aside happen together, with the
            // variants locked, so two shoppers can't both claim the last piece.
            $order = DB::transaction(function () use ($cart, $validated, $items, $quote, $saveCard, &$isNewOrder) {
                $shortage = $this->stockShortage($cart, $items);

                if ($shortage) {
                    return $shortage;
                }

                $order = $this->findMatchingUnpaidOrder($cart, $validated, $items, $quote);
                $isNewOrder = ! $order;

                if ($order) {
                    $order->update(['save_card' => $saveCard, 'notes' => $validated['notes'] ?? null]);
                } else {
                    $order = $this->createOrder($cart, $validated, $items, $quote, $saveCard);
                }

                $order->update(['stock_held_until' => now()->addMinutes(config('store.stock_hold_minutes'))]);

                return $order;
            });

            if (is_string($order)) {
                return back()->withErrors(['cart' => $order]);
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
                        // Bank transfer, USSD and QR only work with Nigerian accounts.
                        'channels' => $quote['is_international'] ? ['card'] : ['card', 'bank', 'bank_transfer', 'ussd', 'qr'],
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
        $countries = array_keys(StoreSetting::current()->internationalCountries());
        $abroad = in_array($request->input('country'), $countries, true);

        $validated = $request->validate([
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:120',
            'country' => ['nullable', 'string', Rule::in(['NG', ...$countries])],
            // Overseas the fee is per country, so the region doesn't matter for the price.
            'state' => $abroad ? ['nullable', 'string', 'max:120'] : ['required', 'string', Rule::in(config('store.nigeria_states'))],
        ]);

        $items = $this->existingCartItems();
        $subtotal = (float) $items->sum(fn ($i) => $i->variant->final_price * $i->quantity);

        return response()->json(['success' => true] + $pricing->quote($subtotal, $validated));
    }

    /**
     * Validates the order details plus an address in Nigeria or in a country the
     * store has switched on. Returns shipping_country as the country's name and
     * shipping_country_code alongside it.
     *
     * @param  array<string, mixed>  $rules
     * @param  array<string, string>  $messages
     * @return array<string, mixed>
     */
    private function validateAddress(Request $request, array $rules, array $messages = []): array
    {
        $countries = StoreSetting::current()->internationalCountries();
        $code = $request->input('shipping_country') ?: 'NG';
        $abroad = $countries[$code] ?? null;

        $rules['shipping_country'] = ['nullable', 'string', Rule::in(['NG', ...array_keys($countries)])];

        if ($abroad) {
            $rules['shipping_state'] = [$abroad['region_required'] ? 'required' : 'nullable', 'string', 'max:120'];
            $rules['shipping_postcode'] = ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9][A-Za-z0-9 \-]{1,18}$/'];
            $messages['shipping_state.required'] = "Please enter your {$abroad['region_label']}.";
            $messages['shipping_postcode.required'] = "Please enter your {$abroad['postcode_label']}.";
            $messages['shipping_postcode.regex'] = "Please enter a valid {$abroad['postcode_label']}.";
        } else {
            $rules['shipping_state'] = ['required', 'string', Rule::in(config('store.nigeria_states'))];
            $rules['shipping_postcode'] = ['nullable', 'string', 'max:20'];
        }

        $messages['shipping_country.in'] = "Sorry, we don't deliver to that country yet.";

        $validated = $request->validate($rules, $messages);
        $validated['shipping_country_code'] = $abroad ? $code : 'NG';
        $validated['shipping_country'] = $abroad ? $abroad['name'] : 'Nigeria';
        $validated['shipping_state'] = (string) ($validated['shipping_state'] ?? '');
        $validated['shipping_postcode'] = isset($validated['shipping_postcode']) ? strtoupper(trim($validated['shipping_postcode'])) : null;

        return $validated;
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
                'shipping_postcode' => $order->shipping_postcode,
                'shipping_country' => $order->shipping_country,
                'full_address' => $order->fullAddress(),
                'is_international' => $order->isInternational(),
                'shipping_fee' => $order->shipping_fee,
                'shipping_carrier' => $order->shipping_carrier,
                'tracking_number' => $order->tracking_number,
                'tracking_url' => $order->trackingUrl(),
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
    /**
     * Locks the bag's variants and checks each still has enough stock once
     * other shoppers' unpaid holds are set aside. Must run inside a transaction.
     *
     * @param  Collection<int, mixed>  $items
     * @return string|null Why the bag can't be paid for, or null when it can.
     */
    private function stockShortage(Cart $cart, Collection $items): ?string
    {
        $ids = $items->pluck('product_variant_id')->unique()->sort()->values();

        // Locked in id order so two checkouts never wait on each other in a circle.
        $stock = ProductVariant::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->pluck('stock_quantity', 'id');

        $held = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('order_items.product_variant_id', $ids)
            ->where('orders.payment_status', 'unpaid')
            ->where('orders.status', 'pending')
            ->where('orders.stock_held_until', '>', now())
            // This bag's own earlier attempt doesn't count against it.
            ->where(fn ($q) => $q->whereNull('orders.cart_id')->orWhere('orders.cart_id', '!=', $cart->id))
            ->groupBy('order_items.product_variant_id')
            ->selectRaw('order_items.product_variant_id as variant_id, SUM(order_items.quantity) as quantity')
            ->pluck('quantity', 'variant_id');

        foreach ($items as $item) {
            $available = (int) ($stock[$item->product_variant_id] ?? 0) - (int) ($held[$item->product_variant_id] ?? 0);

            if ($item->quantity > $available) {
                $name = $item->variant->product->name.($item->variant->size ? " ({$item->variant->size})" : '');

                return $available <= 0
                    ? "Someone else is paying for the last '{$name}' right now. If they don't finish, it will be available again in a few minutes."
                    : "Only {$available} '{$name}' can be bought right now, because others are being paid for. Please update your bag.";
            }
        }

        return null;
    }

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
            ->where('shipping_country', $details['shipping_country'])
            ->where('shipping_postcode', $details['shipping_postcode'])
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
                'routely_fee' => $quote['is_international'] ? null : $quote['courier_fee'],
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
                'shipping_postcode' => $details['shipping_postcode'],
                'shipping_country' => $details['shipping_country'],
                'shipping_carrier' => $quote['is_international'] ? $quote['carrier'] : null,
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
            'shipping_country' => $order->shipping_country,
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
