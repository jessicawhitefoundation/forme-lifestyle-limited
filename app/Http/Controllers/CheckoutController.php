<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\OrderMailService;
use App\Services\RoutelyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    private function getCart(): Cart
    {
        if (auth()->check()) {
            return Cart::firstOrCreate(['user_id' => auth()->id()]);
        }

        return Cart::firstOrCreate(['session_id' => session()->getId()]);
    }

    public function show(): Response|RedirectResponse
    {
        $cart = $this->getCart();
        $items = $cart->items()->with(['variant.product.images'])->get();

        if ($items->isEmpty()) {
            return redirect()->route('cart.show');
        }

        $subtotal = $items->sum(fn ($i) => $i->variant->final_price * $i->quantity);
        $user = auth()->user();

        $initialState = $user?->shipping_state ?? 'Lagos';
        $initialCity = $user?->shipping_city ?? 'Lekki';
        $initialAddr = $user?->shipping_address ?? 'Lekki';

        $quote = app(RoutelyService::class)->getFee([
            'address' => $initialAddr,
            'city' => $initialCity,
            'state' => $initialState,
            'item_value' => $subtotal,
        ]);

        $shippingFee = (float) ($quote['rate'] ?? 3000);
        $carrierInfo = $quote['carrier'] ?? 'Routely Delivery';
        $deliveryTime = $quote['delivery_time'] ?? '4 to 8 working hours';

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
            'subtotal' => $subtotal,
            'shippingFee' => $shippingFee,
            'carrierInfo' => $carrierInfo,
            'deliveryTime' => $deliveryTime,
            'total' => $subtotal + $shippingFee,
            'user' => $user,
            // Pass saved card info so frontend can show "Pay with saved card" toggle
            'savedCard' => $user && $user->paystack_auth_code ? [
                'last_four' => $user->card_last_four,
                'brand' => $user->card_brand,
            ] : null,
        ]);
    }

    public function initiate(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string',
            'customer_email' => 'required|email',
            'customer_phone' => 'nullable|string',
            'shipping_address' => 'required|string',
            'shipping_city' => 'required|string',
            'shipping_state' => 'required|string',
            'use_saved_card' => 'nullable|boolean',
        ]);

        $cart = $this->getCart();
        $items = $cart->items()->with(['variant.product'])->get();

        if ($items->isEmpty()) {
            return back()->withErrors(['cart' => 'Your cart is empty']);
        }

        // ── Pre-checkout Inventory & Availability Check ───────────────────────
        foreach ($items as $item) {
            $variant = $item->variant;
            if (! $variant || ! $variant->is_active || ($variant->product && ! $variant->product->is_active)) {
                return back()->withErrors(['cart' => 'One or more items in your cart is no longer available. Please update your cart.']);
            }

            if ($variant->stock_quantity < $item->quantity) {
                $name = $variant->product?->name ?? 'An item';
                $size = $variant->size ? " ({$variant->size})" : '';

                return back()->withErrors(['cart' => "Sorry, '{$name}{$size}' only has {$variant->stock_quantity} in stock. Please adjust quantity."]);
            }
        }

        // ── Concurrency & Idempotency: Atomic lock per user/session ───────────
        $lockKey = 'checkout_lock_'.(auth()->id() ?: session()->getId());
        $lock = Cache::lock($lockKey, 10);

        if (! $lock->get()) {
            return back()->withErrors(['checkout' => 'A checkout request is already processing. Please wait a moment.']);
        }

        $subtotal = $items->sum(fn ($i) => $i->variant->final_price * $i->quantity);

        $quote = app(RoutelyService::class)->getFee([
            'address' => $request->shipping_address,
            'city' => $request->shipping_city,
            'state' => $request->shipping_state,
            'item_value' => $subtotal,
        ]);
        $shippingFee = (float) ($quote['rate'] ?? 3000);

        $total = $subtotal + $shippingFee;

        // ── Idempotency Check: Reuse recent unpaid pending order if duplicate request ──
        $existingOrder = Order::where('customer_email', $request->customer_email)
            ->where('payment_status', 'unpaid')
            ->where('total_amount', $total)
            ->where('created_at', '>=', now()->subMinutes(3))
            ->latest()
            ->first();

        if ($existingOrder) {
            $order = $existingOrder;
        } else {
            // Create order in pending state
            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'user_id' => auth()->id(),
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
                'total_amount' => $total,
                'customer_name' => $request->customer_name,
                'customer_email' => $request->customer_email,
                'customer_phone' => $request->customer_phone,
                'shipping_address' => $request->shipping_address,
                'shipping_city' => $request->shipping_city,
                'shipping_state' => $request->shipping_state,
                'notes' => $request->notes,
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
        }

        // Remember order ID in session for authorization access on confirmation page
        session(['recent_order_id' => $order->id]);

        $paystackKey = config('services.paystack.secret_key');

        if (! $paystackKey || str_contains($paystackKey, 'your_secret_key_here')) {
            Log::error('Paystack initialization failed: Placeholder or missing PAYSTACK_SECRET_KEY in .env');

            return back()->withErrors(['payment' => 'Payment failed: Paystack API key is not configured in .env. Please set PAYSTACK_SECRET_KEY.']);
        }

        // ── Pay with saved card (charge authorization) ──────────────────────
        $user = auth()->user();
        if ($request->boolean('use_saved_card') && $user && $user->paystack_auth_code) {
            $chargeResponse = Http::withToken($paystackKey)
                ->post('https://api.paystack.co/transaction/charge_authorization', [
                    'email' => $request->customer_email,
                    'amount' => (int) ($total * 100),
                    'currency' => 'NGN',
                    'authorization_code' => $user->paystack_auth_code,
                    'reference' => $order->order_number,
                    'metadata' => $this->buildMetadata($order, $items),
                ]);

            if ($chargeResponse->successful() && $chargeResponse->json('data.status') === 'success') {
                return $this->markOrderPaid($order, $chargeResponse->json('data'));
            }

            // Charge failed — fall through to standard redirect checkout
            Log::warning('Saved-card charge failed for order '.$order->order_number, [
                'status' => $chargeResponse->status(),
                'response' => $chargeResponse->json() ?? $chargeResponse->body(),
            ]);
        }

        // ── Standard Paystack redirect checkout ─────────────────────────────
        $response = Http::withToken($paystackKey)
            ->post('https://api.paystack.co/transaction/initialize', [
                'email' => $request->customer_email,
                'amount' => (int) ($total * 100),
                'currency' => 'NGN',
                'reference' => $order->order_number,
                'channels' => ['card', 'bank', 'bank_transfer', 'ussd', 'qr'],
                'metadata' => $this->buildMetadata($order, $items),
                'callback_url' => route('checkout.callback'),
            ]);

        if (! $response->successful()) {
            $errorMsg = $response->json('message') ?? 'Payment initialization failed. Please try again.';
            Log::error('Paystack transaction/initialize error for order '.$order->order_number, [
                'http_status' => $response->status(),
                'response' => $response->json() ?? $response->body(),
            ]);
            $order->delete();

            return back()->withErrors(['payment' => 'Paystack Error: '.$errorMsg]);
        }

        return Inertia::location($response->json('data.authorization_url'));
    }

    public function callback(Request $request)
    {
        $reference = $request->reference;
        if (! $reference) {
            return redirect()->route('home')->withErrors(['payment' => 'Invalid payment reference']);
        }

        $paystackKey = config('services.paystack.secret_key');
        $response = Http::withToken($paystackKey)
            ->get("https://api.paystack.co/transaction/verify/{$reference}");

        if (! $response->successful()) {
            return redirect()->route('checkout.show')->withErrors(['payment' => 'Payment verification failed']);
        }

        $data = $response->json('data');
        $order = Order::where('order_number', $reference)->first();

        if (! $order) {
            return redirect()->route('home');
        }

        // ── Financial Security: Strict Amount & Currency Validation ───────────
        $expectedKobo = (int) round($order->total_amount * 100);
        $paidKobo = (int) ($data['amount'] ?? 0);
        $currency = $data['currency'] ?? '';

        if ($paidKobo !== $expectedKobo || $currency !== 'NGN') {
            Log::critical("Paystack payment verification amount/currency mismatch on Order #{$order->order_number}: Expected {$expectedKobo} NGN, received {$paidKobo} {$currency}");

            return redirect()->route('checkout.show')->withErrors(['payment' => 'Payment verification mismatch. Please contact support.']);
        }

        if ($data['status'] === 'success') {
            return $this->markOrderPaid($order, $data);
        }

        return redirect()->route('checkout.show')->withErrors(['payment' => 'Payment was not completed']);
    }

    /**
     * Shared "mark order paid" logic with pessimistic row-locking & database transaction.
     */
    private function markOrderPaid(Order $order, array $paystackData)
    {
        return DB::transaction(function () use ($order, $paystackData) {
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->first();

            if (! $lockedOrder || $lockedOrder->payment_status === 'paid') {
                // Already processed (e.g. webhook arrived first)
                return redirect()->route('order.confirmation', $order->order_number);
            }

            $lockedOrder->update([
                'payment_status' => 'paid',
                'payment_reference' => $paystackData['reference'],
                'status' => 'processing',
            ]);

            // Save card token for future one-click checkout
            $user = auth()->user() ?? User::find($lockedOrder->user_id);
            if (
                $user && ! empty($paystackData['authorization']['reusable'])
                && $paystackData['authorization']['reusable']
            ) {
                $user->update([
                    'paystack_auth_code' => $paystackData['authorization']['authorization_code'],
                    'card_last_four' => $paystackData['authorization']['last4'],
                    'card_brand' => $paystackData['authorization']['brand'],
                ]);
            }

            // Deduct stock safely without allowing negative inventory
            foreach ($lockedOrder->items as $item) {
                if ($item->variant) {
                    $item->variant->update([
                        'stock_quantity' => DB::raw('GREATEST(0, stock_quantity - '.(int) $item->quantity.')'),
                    ]);
                }
            }

            // Clear the cart
            $cart = $this->getCart();
            $cart->items()->delete();

            // Send order confirmation to customer and notification to store owner
            OrderMailService::sendOrderPaidNotifications($lockedOrder);

            return redirect()->route('order.confirmation', $lockedOrder->order_number);
        });
    }

    /**
     * Build Paystack metadata with cart line items for display on Paystack page.
     */
    private function buildMetadata(Order $order, $items): array
    {
        $customFields = $items->map(fn ($item) => [
            'display_name' => $item->variant->product->name,
            'variable_name' => 'item_'.$item->variant->id,
            'value' => $item->variant->description
                ? $item->variant->description.' × '.$item->quantity
                : 'Qty: '.$item->quantity,
        ])->toArray();

        return [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'custom_fields' => $customFields,
        ];
    }

    public function calculateShippingRate(Request $request, RoutelyService $routely)
    {
        $request->validate([
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'required|string',
        ]);

        $cart = $this->getCart();
        $items = $cart->items()->with(['variant.product'])->get();
        $subtotal = $items->sum(fn ($i) => $i->variant->final_price * $i->quantity);

        $quote = $routely->getFee([
            'address' => $request->address,
            'city' => $request->city,
            'state' => $request->state,
            'item_value' => $subtotal,
        ]);

        $rate = (float) ($quote['rate'] ?? 3000);
        $carrier = $quote['carrier'] ?? ('Routely '.ucfirst($quote['type'] ?? 'Standard'));
        $deliveryTime = $quote['delivery_time'] ?? null;

        return response()->json([
            'success' => true,
            'shipping_fee' => $rate,
            'carrier' => $carrier,
            'delivery_time' => $deliveryTime,
            'type' => $quote['type'] ?? 'standard',
            'source' => $quote['source'] ?? 'routely',
        ]);
    }

    public function confirmation(string $orderNumber): Response
    {
        $order = Order::with('items')->where('order_number', $orderNumber)->firstOrFail();

        // ── IDOR Protection: Only the order owner, recent checkout session, or store admin may view ──
        $isOwner = auth()->check() && (int) auth()->id() === (int) $order->user_id;
        $isRecentSession = session('recent_order_id') === $order->id;
        $isAdmin = auth()->check() && auth()->user()->isAdmin();

        if (! $isOwner && ! $isRecentSession && ! $isAdmin) {
            abort(403);
        }

        return Inertia::render('Store/OrderConfirmation', [
            'order' => [
                'order_number' => $order->order_number,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'total_amount' => $order->total_amount,
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
}
