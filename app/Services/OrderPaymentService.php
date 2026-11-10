<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The one place an order becomes paid. The customer's return from Paystack and
 * Paystack's webhook can arrive in either order, so this must be safe to repeat.
 */
class OrderPaymentService
{
    public const PAID = 'paid';

    public const ALREADY_PAID = 'already_paid';

    public const MISMATCH = 'mismatch';

    public const NOT_SUCCESSFUL = 'not_successful';

    /**
     * @param  array<string, mixed>  $paystackData  The transaction data returned by Paystack.
     */
    public function markPaid(Order $order, array $paystackData): string
    {
        if (($paystackData['status'] ?? null) !== 'success') {
            return self::NOT_SUCCESSFUL;
        }

        $result = DB::transaction(function () use ($order, $paystackData) {
            /** @var Order|null $locked */
            $locked = Order::whereKey($order->id)->lockForUpdate()->first();

            if (! $locked) {
                return self::MISMATCH;
            }

            if ($locked->isPaid()) {
                return self::ALREADY_PAID;
            }

            $expectedKobo = (int) round((float) $locked->total_amount * 100);
            $paidKobo = (int) ($paystackData['amount'] ?? 0);
            $currency = $paystackData['currency'] ?? '';

            if ($paidKobo !== $expectedKobo || $currency !== 'NGN') {
                Log::critical("Paystack amount/currency mismatch on order {$locked->order_number}: expected {$expectedKobo} NGN, received {$paidKobo} {$currency}");

                return self::MISMATCH;
            }

            $locked->update([
                'payment_status' => 'paid',
                'payment_reference' => $paystackData['reference'] ?? null,
                'status' => 'processing',
            ]);

            $this->saveCardIfRequested($locked, $paystackData);
            $this->deductStock($locked);
            $this->removePurchasedItemsFromCart($locked);

            return self::PAID;
        });

        if ($result === self::PAID) {
            OrderMailService::sendOrderPaidNotifications($order->fresh());
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $paystackData
     */
    private function saveCardIfRequested(Order $order, array $paystackData): void
    {
        $authorization = $paystackData['authorization'] ?? [];

        if (! $order->save_card || ! $order->user_id || empty($authorization['reusable'])) {
            return;
        }

        User::whereKey($order->user_id)->update([
            'paystack_auth_code' => $authorization['authorization_code'] ?? null,
            'card_last_four' => $authorization['last4'] ?? null,
            'card_brand' => $authorization['brand'] ?? null,
        ]);
    }

    private function deductStock(Order $order): void
    {
        foreach ($order->items as $item) {
            /** @var ProductVariant|null $variant */
            $variant = ProductVariant::whereKey($item->product_variant_id)->lockForUpdate()->first();

            if (! $variant) {
                continue;
            }

            if ($variant->stock_quantity < $item->quantity) {
                Log::critical("Order {$order->order_number} paid for {$item->quantity} of variant {$variant->id} but only {$variant->stock_quantity} left in stock.");
            }

            $variant->update(['stock_quantity' => max(0, $variant->stock_quantity - $item->quantity)]);
        }
    }

    private function removePurchasedItemsFromCart(Order $order): void
    {
        if (! $order->cart_id) {
            return;
        }

        CartItem::where('cart_id', $order->cart_id)
            ->whereIn('product_variant_id', $order->items->pluck('product_variant_id')->filter())
            ->delete();
    }
}
