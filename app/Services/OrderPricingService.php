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
     * @param  array{address?: ?string, city?: ?string, state?: ?string, country?: ?string}  $destination
     * @return array{courier_fee: float, shipping_fee: float, free_shipping: bool, free_shipping_remaining: float, is_lagos: bool, is_international: bool, country: string, carrier: string, delivery_time: ?string, vat_amount: float, vat_inclusive: bool, subtotal: float, total: float, approx: ?array{currency: string, symbol: string, total: float}}
     */
    public function quote(float $subtotal, array $destination): array
    {
        $settings = StoreSetting::current();
        $country = $destination['country'] ?? null;

        if ($country && $country !== 'NG' && $country !== 'Nigeria') {
            $abroad = $settings->internationalCountry($country);

            // Checkout validation only lets enabled countries through.
            if (! $abroad) {
                throw new \InvalidArgumentException("The store doesn't deliver to {$country}.");
            }

            return $this->internationalQuote($subtotal, $abroad, $settings);
        }

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
            'is_international' => false,
            'country' => 'Nigeria',
            // Routely adds notes for the store in brackets, which customers don't need.
            'carrier' => trim((string) preg_replace('/\s*\(.*?\)/', '', (string) ($courier['carrier'] ?? 'Routely Delivery'))),
            'delivery_time' => $courier['delivery_time'] ?? null,
            'vat_amount' => $vat,
            'vat_inclusive' => (bool) $settings->vat_inclusive,
            'subtotal' => $subtotal,
            'total' => round($subtotal + $shippingFee + $vatAdded, 2),
            'approx' => null,
        ];
    }

    /**
     * Overseas delivery: the flat fee Jessica set for the country, sent by DHL,
     * still charged in naira, with the total shown roughly in the local currency.
     *
     * Exports are zero-rated for Nigerian VAT, so no VAT is added or recorded,
     * even when VAT is switched on for Nigerian orders.
     *
     * @param  array{name: string, currency: string, symbol: string, fee: float, rate: ?float}  $country
     */
    private function internationalQuote(float $subtotal, array $country, StoreSetting $settings): array
    {
        $vat = 0.0;
        $total = round($subtotal + $country['fee'], 2);

        return [
            'courier_fee' => $country['fee'],
            'shipping_fee' => $country['fee'],
            'free_shipping' => false,
            'free_shipping_remaining' => 0.0,
            'is_lagos' => false,
            'is_international' => true,
            'country' => $country['name'],
            'carrier' => (string) config('store.international_carrier'),
            'delivery_time' => $settings->international_delivery_time ?: null,
            'vat_amount' => $vat,
            'vat_inclusive' => (bool) $settings->vat_inclusive,
            'subtotal' => $subtotal,
            'total' => $total,
            'approx' => $country['rate'] ? [
                'currency' => $country['currency'],
                'symbol' => $country['symbol'],
                'total' => round($total / $country['rate'], 2),
            ] : null,
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
