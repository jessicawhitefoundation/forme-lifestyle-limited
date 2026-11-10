<?php

namespace App\Services;

use App\Models\StoreSetting;

/**
 * Works out what a customer pays: delivery, free-delivery rule, VAT and total.
 * The cart estimator, checkout page and order creation all use this, so the
 * price a customer sees is the price they are charged.
 */
class OrderPricingService
{
    public function __construct(private RoutelyService $routely) {}

    /**
     * @param  array{address?: ?string, city?: ?string, state?: ?string}  $destination
     * @return array{courier_fee: float, shipping_fee: float, free_shipping: bool, free_shipping_remaining: float, is_lagos: bool, carrier: string, delivery_time: ?string, vat_amount: float, vat_inclusive: bool, subtotal: float, total: float}
     */
    public function quote(float $subtotal, array $destination): array
    {
        $settings = StoreSetting::current();
        $state = (string) ($destination['state'] ?? '');

        $courier = $this->routely->getFee([
            'address' => $destination['address'] ?? null,
            'city' => $destination['city'] ?? null,
            'state' => $state,
            'item_value' => $subtotal,
        ]);

        $courierFee = (float) ($courier['rate'] ?? 3000);
        $isLagos = RoutelyService::normalizeState($state) === 'Lagos';
        $threshold = (float) $settings->free_shipping_threshold;

        $freeShipping = $settings->free_shipping_enabled && $isLagos && $subtotal >= $threshold;
        $shippingFee = $freeShipping ? 0.0 : $courierFee;

        $vat = $this->vat($subtotal, $settings);
        $vatAdded = $settings->vat_enabled && ! $settings->vat_inclusive ? $vat : 0.0;

        return [
            'courier_fee' => $courierFee,
            'shipping_fee' => $shippingFee,
            'free_shipping' => $freeShipping,
            'free_shipping_remaining' => $settings->free_shipping_enabled && $isLagos && ! $freeShipping
                ? round($threshold - $subtotal, 2)
                : 0.0,
            'is_lagos' => $isLagos,
            // Routely adds notes for the store in brackets, which customers don't need.
            'carrier' => trim((string) preg_replace('/\s*\(.*?\)/', '', (string) ($courier['carrier'] ?? 'Routely Delivery'))),
            'delivery_time' => $courier['delivery_time'] ?? null,
            'vat_amount' => $vat,
            'vat_inclusive' => (bool) $settings->vat_inclusive,
            'subtotal' => $subtotal,
            'total' => round($subtotal + $shippingFee + $vatAdded, 2),
        ];
    }

    private function vat(float $subtotal, StoreSetting $settings): float
    {
        if (! $settings->vat_enabled) {
            return 0.0;
        }

        $rate = (float) $settings->vat_rate / 100;

        return $settings->vat_inclusive
            ? round($subtotal - ($subtotal / (1 + $rate)), 2)
            : round($subtotal * $rate, 2);
    }
}
