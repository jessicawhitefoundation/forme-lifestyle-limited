<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderMailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaystackWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Verify the webhook signature
        $secret = config('services.paystack.secret_key');
        $signature = $request->header('X-Paystack-Signature');
        $computed = hash_hmac('sha512', $request->getContent(), $secret);

        if (! hash_equals($computed, (string) $signature)) {
            Log::warning('Paystack webhook: invalid signature');

            return response('Forbidden', 403);
        }

        $event = $request->json('event');
        $data = $request->json('data');

        if ($event !== 'charge.success') {
            return response('OK', 200);
        }

        $reference = $data['reference'] ?? null;
        if (! $reference) {
            return response('OK', 200);
        }

        return DB::transaction(function () use ($reference, $data) {
            $order = Order::where('order_number', $reference)->lockForUpdate()->first();

            if (! $order || $order->payment_status === 'paid') {
                return response('OK', 200);
            }

            // ── Financial Security: Strict Amount & Currency Validation ───────────
            $expectedKobo = (int) round($order->total_amount * 100);
            $paidKobo = (int) ($data['amount'] ?? 0);
            $currency = $data['currency'] ?? '';

            if ($paidKobo !== $expectedKobo || $currency !== 'NGN') {
                Log::critical("Paystack webhook amount/currency mismatch on Order #{$order->order_number}: Expected {$expectedKobo} NGN, received {$paidKobo} {$currency}");

                return response('Mismatch', 400);
            }

            $order->update([
                'payment_status' => 'paid',
                'payment_reference' => $data['reference'],
                'status' => 'processing',
            ]);

            // Save card token if available
            if ($order->user_id && ! empty($data['authorization']['reusable']) && $data['authorization']['reusable']) {
                $user = User::find($order->user_id);
                if ($user && ! $user->paystack_auth_code) {
                    $user->update([
                        'paystack_auth_code' => $data['authorization']['authorization_code'],
                        'card_last_four' => $data['authorization']['last4'],
                        'card_brand' => $data['authorization']['brand'],
                    ]);
                }
            }

            // Deduct stock safely without allowing negative inventory
            foreach ($order->items as $item) {
                if ($item->variant) {
                    $item->variant->update([
                        'stock_quantity' => DB::raw('GREATEST(0, stock_quantity - '.(int) $item->quantity.')'),
                    ]);
                }
            }

            // Clear cart
            $cart = $order->user_id
                ? Cart::where('user_id', $order->user_id)->first()
                : null;

            if ($cart) {
                $cart->items()->delete();
            }

            // Send order confirmation to customer and notification to store owner
            OrderMailService::sendOrderPaidNotifications($order);

            Log::info("Paystack webhook: order {$order->order_number} marked paid via webhook");

            return response('OK', 200);
        });
    }
}
