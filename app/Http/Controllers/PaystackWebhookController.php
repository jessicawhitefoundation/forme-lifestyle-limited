<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaystackWebhookController extends Controller
{
    public function handle(Request $request, OrderPaymentService $payments)
    {
        $signature = (string) $request->header('X-Paystack-Signature');
        $computed = hash_hmac('sha512', $request->getContent(), (string) config('services.paystack.secret_key'));

        if (! hash_equals($computed, $signature)) {
            Log::warning('Paystack webhook: invalid signature');

            return response('Forbidden', 403);
        }

        $data = $request->json('data');

        if ($request->json('event') !== 'charge.success' || ! is_array($data)) {
            return response('OK', 200);
        }

        $reference = $data['reference'] ?? null;
        $order = Order::findByPaymentReference($reference);

        if (! $order) {
            Log::critical("Paystack webhook: successful payment with no matching order, reference {$reference}");

            return response('OK', 200);
        }

        // Paystack retries anything other than a 200, and retrying cannot fix a mismatch.
        $result = $payments->markPaid($order, $data);
        Log::info("Paystack webhook: order {$order->order_number} result {$result}");

        return response('OK', 200);
    }
}
