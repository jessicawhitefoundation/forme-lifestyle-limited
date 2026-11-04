<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RoutelyService
{
    protected string $baseUrl;

    protected ?string $apiKey;

    protected string $env;

    /** @var array<string, mixed> */
    protected array $sender;

    /**
     * Default geographic coordinate fallbacks for common Nigerian states & Lagos districts
     *
     * @var array<string, string>
     */
    protected static array $coordinates = [
        // Lagos areas (sorted with specific neighborhoods first)
        'victoria island' => '6.433849,3.425999',
        'vi' => '6.433849,3.425999',
        'ikoyi' => '6.454900,3.435000',
        'banana island' => '6.459000,3.447000',
        'oniru' => '6.430000,3.440000',
        'elegushi' => '6.438000,3.490000',
        'ikate' => '6.442000,3.500000',
        'osapa' => '6.440000,3.515000',
        'chevron' => '6.442000,3.535000',
        'lekki' => '6.448196,3.474101',
        'ajah' => '6.465800,3.568400',
        'sangotedo' => '6.471800,3.626400',
        'abijo' => '6.474000,3.693000',
        'awoyaya' => '6.478000,3.722000',
        'epe' => '6.584100,3.983400',
        'marina' => '6.454900,3.388600',
        'obalende' => '6.449700,3.411600',
        'yaba' => '6.509500,3.379000',
        'surulere' => '6.497500,3.356200',
        'gbagada' => '6.554000,3.388800',
        'maryland' => '6.572400,3.368300',
        'ilupeju' => '6.553900,3.365300',
        'anthony' => '6.561500,3.371200',
        'ikeja' => '6.601800,3.351500',
        'magodo' => '6.621200,3.378900',
        'ogba' => '6.634100,3.342100',
        'agege' => '6.618000,3.320900',
        'alimosho' => '6.609400,3.259200',
        'egbeda' => '6.597000,3.292000',
        'ipaja' => '6.607400,3.273000',
        'oshodi' => '6.544000,3.344000',
        'festac' => '6.467400,3.284200',
        'apapa' => '6.447400,3.361900',
        'ikorodu' => '6.619400,3.510500',
        'badagry' => '6.431600,2.887600',
        'lagos' => '6.452000,3.398000',

        // Nigerian States
        'abuja' => '9.076500,7.398600',
        'fct' => '9.076500,7.398600',
        'rivers' => '4.815600,7.049800',
        'port harcourt' => '4.815600,7.049800',
        'oyo' => '7.377500,3.947000',
        'ibadan' => '7.377500,3.947000',
        'ogun' => '7.147500,3.361900',
        'abeokuta' => '7.147500,3.361900',
        'edo' => '6.335000,5.603700',
        'benin' => '6.335000,5.603700',
        'enugu' => '6.458400,7.546400',
        'anambra' => '6.220900,7.072200',
        'delta' => '5.544200,5.760300',
        'asaba' => '6.198400,6.732900',
        'warri' => '5.516700,5.750000',
        'kano' => '12.002200,8.592000',
        'kaduna' => '10.510500,7.416500',
    ];

    public function __construct()
    {
        $this->baseUrl = (string) config('services.routely.base_url', 'https://server.routely.co/api');
        $rawApiKey = config('services.routely.api_key');
        $this->apiKey = is_string($rawApiKey) && $rawApiKey !== '' ? $rawApiKey : null;
        $this->env = (string) config('services.routely.env', 'test');
        $this->sender = (array) config('services.routely.sender', []);
    }

    /**
     * Resolve coordinate string (lat,lng) from text address / city / state.
     */
    public static function resolveCoordinates(string $address, ?string $city = null, ?string $state = null): string
    {
        $search = strtolower("{$address} {$city} {$state}");

        foreach (self::$coordinates as $key => $coords) {
            if (str_contains($search, $key)) {
                return $coords;
            }
        }

        // Default to Lagos coordinates if in Lagos or default
        return str_contains(strtolower($state ?? ''), 'lagos') || empty($state)
            ? '6.452000,3.398000'
            : '9.076500,7.398600'; // Default to central Nigeria
    }

    /**
     * Get shipment fee quote from Routely API.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function getFee(array $params): array
    {
        $toState = $params['state'] ?? 'Lagos';
        $toCity = $params['city'] ?? 'Lagos';
        $toAddress = $params['address'] ?? '';
        $itemValue = (float) ($params['item_value'] ?? 25000);
        $weight = (float) ($params['weight'] ?? 1.5);

        $fromAddress = $this->sender['address'] ?? 'Lekki Phase 1, Lagos';
        $fromCoords = $this->sender['coordinates'] ?? '6.4426,3.4833';
        $toCoords = $params['to_coordinates'] ?? self::resolveCoordinates($toAddress, $toCity, $toState);

        $isIntracity = strcasecmp(trim($toState), 'lagos') === 0;

        if (empty($this->apiKey)) {
            return [
                'success' => true,
                'rate' => $isIntracity ? 3000.0 : 6500.0,
                'carrier' => $isIntracity ? 'Formé Express (Lagos)' : 'Formé Interstate Courier',
                'delivery_time' => $isIntracity ? '1-2 business days' : '3-5 business days',
                'fallback' => true,
            ];
        }

        try {
            if ($isIntracity) {
                $payload = [
                    'from' => $fromAddress,
                    'from_coordinates' => $fromCoords,
                    'to' => $toAddress ?: 'Lekki, Lagos',
                    'to_coordinates' => $toCoords,
                    'package_type' => 'package',
                    'vehicle_type' => 1, // Bike / Courier
                    'item_value' => $itemValue,
                    'has_insurance' => false,
                    'has_pickup' => true,
                ];
            } else {
                $payload = [
                    'from' => $fromAddress,
                    'from_state' => 'Lagos',
                    'from_city' => 'Lekki',
                    'from_coordinates' => $fromCoords,
                    'to' => $toAddress ?: $toCity,
                    'to_state' => $toState,
                    'to_city' => $toCity,
                    'to_coordinates' => $toCoords,
                    'package_type' => 'package',
                    'weight' => $weight,
                    'has_packaging' => false,
                    'item_value' => $itemValue,
                    'has_insurance' => false,
                    'has_pickup' => true,
                    'service_station' => 82, // Required by Routely for Lagos origin
                ];
            }

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'x-api-key' => $this->apiKey,
            ])->timeout(12)->post("{$this->baseUrl}/v1/business/shipments/fee", $payload);

            if ($response->successful() && ($response->json('success') === true)) {
                $rates = $response->json('data.rates', []);
                $cheapestRate = null;

                if (! empty($rates)) {
                    // Pick the best rate (or first available rate)
                    $cheapestRate = $rates[0];
                }

                return [
                    'success' => true,
                    'rate' => $cheapestRate ? (float) $cheapestRate['selling_price'] : 3000.0,
                    'carrier' => $cheapestRate['carrier_name'] ?? 'Routely Standard',
                    'delivery_time' => $cheapestRate['delivery_time'] ?? ($isIntracity ? '1-2 business days' : '3-5 business days'),
                    'cachekey' => $response->json('data.cachekey'),
                    'all_rates' => $rates,
                ];
            }

            Log::warning('Routely fee calculation non-success response', [
                'payload' => $payload,
                'response' => $response->json(),
                'status' => $response->status(),
            ]);

            // Fallback based on location
            return [
                'success' => false,
                'rate' => $isIntracity ? 3000.0 : 6500.0,
                'carrier' => $isIntracity ? 'Formé Express (Lagos)' : 'Formé Interstate Courier',
                'delivery_time' => $isIntracity ? '2-3 business days' : '3-7 business days',
                'fallback' => true,
                'error' => $response->json('message') ?? 'Could not calculate real-time fee',
            ];
        } catch (\Throwable $e) {
            Log::error('Routely fee calculation exception: '.$e->getMessage());

            return [
                'success' => false,
                'rate' => $isIntracity ? 3000.0 : 6500.0,
                'carrier' => 'Standard Courier',
                'delivery_time' => '3-5 business days',
                'fallback' => true,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create a shipment with Routely for a placed order.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function createShipment(Order $order, array $options = []): array
    {
        $toState = $order->shipping_state ?: 'Lagos';
        $toCity = $order->shipping_city ?: 'Lekki';
        $toAddress = $order->shipping_address ?: 'Lagos';
        $isIntracity = strcasecmp(trim($toState), 'lagos') === 0;

        if (empty($this->apiKey)) {
            Log::info("Routely API key is not configured. Skipping automated shipment creation for Order #{$order->order_number}.");

            return [
                'success' => false,
                'message' => 'Routely API key is not configured in .env',
            ];
        }

        $fromAddress = $this->sender['address'] ?? 'Lekki Phase 1, Lagos';
        $fromCoords = $this->sender['coordinates'] ?? '6.4426,3.4833';
        $toCoords = self::resolveCoordinates($toAddress, $toCity, $toState);

        $order->loadMissing('items.product');

        $packageItems = [];
        $totalWeight = 0.0;

        /** @var OrderItem $item */
        foreach ($order->items as $item) {
            $itemWeight = 0.5; // Average garment weight ~500g
            $totalWeight += ($itemWeight * $item->quantity);

            $packageItems[] = [
                'description' => "{$item->product_name}".($item->variant_description ? " ({$item->variant_description})" : ''),
                'weight' => $itemWeight,
                'quantity' => $item->quantity,
                'value' => (float) $item->unit_price,
                'length' => 30,
                'width' => 25,
                'height' => 5,
            ];
        }

        if (empty($packageItems)) {
            $packageItems[] = [
                'description' => "Order #{$order->order_number} apparel",
                'weight' => 1.0,
                'quantity' => 1,
                'value' => (float) $order->subtotal,
                'length' => 30,
                'width' => 25,
                'height' => 5,
            ];
            $totalWeight = 1.0;
        }

        try {
            if ($isIntracity) {
                // Intracity creation payload
                $payload = [
                    'senders' => [
                        [
                            'name' => $this->sender['name'] ?? 'FORMÉ Lagos',
                            'address_line_one' => $fromAddress,
                            'city' => $this->sender['city'] ?? 'Lekki',
                            'state' => 'Lagos',
                            'country' => 'Nigeria',
                            'email' => $this->sender['email'] ?? 'hello@forme.ng',
                            'phone_no' => $this->sender['phone'] ?? '+2347026126265',
                            'save_address' => false,
                            'coordinates' => $fromCoords,
                            'postal_code' => '101241',
                        ],
                    ],
                    'receivers' => [
                        [
                            'name' => $order->customer_name,
                            'address_line_one' => $toAddress,
                            'city' => $toCity,
                            'state' => 'Lagos',
                            'country' => 'Nigeria',
                            'email' => $order->customer_email,
                            'phone_no' => $order->customer_phone ?: '+2347000000000',
                            'save_address' => false,
                            'coordinates' => $toCoords,
                            'postal_code' => '100001',
                        ],
                    ],
                    'package_details' => [
                        'package_type' => 'package',
                        'has_pickup' => true,
                        'has_insurance' => false,
                        'description' => "FORMÉ Order #{$order->order_number}",
                        'item_value' => (float) $order->subtotal,
                        'vehicle_type' => 1, // Bike
                    ],
                    'special_instruction' => $order->notes ?: "FORMÉ Order #{$order->order_number}",
                    'total_amount' => (float) ($order->shipping_fee > 0 ? $order->shipping_fee : 2500),
                    'shipment_plan' => 'premium',
                    'payment_method' => 'wallet',
                ];
            } else {
                // Interstate creation payload
                $payload = [
                    'sender' => [
                        'name' => $this->sender['name'] ?? 'FORMÉ Lagos',
                        'address_line_one' => $fromAddress,
                        'city' => $this->sender['city'] ?? 'Lekki',
                        'state' => 'Lagos',
                        'country' => 'Nigeria',
                        'email' => $this->sender['email'] ?? 'hello@forme.ng',
                        'phone_no' => $this->sender['phone'] ?? '+2347026126265',
                        'save_address' => false,
                        'coordinates' => $fromCoords,
                        'postal_code' => '101241',
                    ],
                    'receiver' => [
                        'name' => $order->customer_name,
                        'address_line_one' => $toAddress,
                        'city' => $toCity,
                        'state' => $toState,
                        'country' => 'Nigeria',
                        'email' => $order->customer_email,
                        'phone_no' => $order->customer_phone ?: '+2347000000000',
                        'save_address' => false,
                        'coordinates' => $toCoords,
                        'postal_code' => '000000',
                    ],
                    'package_details' => [
                        'package_type' => 'package',
                        'has_pickup' => true,
                        'has_insurance' => false,
                        'item_value' => (float) $order->subtotal,
                        'weight' => max(1.0, $totalWeight),
                    ],
                    'package_items' => $packageItems,
                    'special_instruction' => $order->notes ?: "FORMÉ Order #{$order->order_number}",
                    'total_amount' => (float) ($order->shipping_fee > 0 ? $order->shipping_fee : 5000),
                    'shipment_plan' => 'premium',
                    'payment_method' => 'wallet',
                ];
            }

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'x-api-key' => $this->apiKey,
            ])->timeout(20)->post("{$this->baseUrl}/v1/business/shipments/create", $payload);

            $data = $response->json();

            if ($response->successful() && ($data['success'] ?? false)) {
                $shipmentId = $data['data']['id'] ?? $data['data']['shipment_id'] ?? null;
                $trackingNo = $data['data']['tracking_number'] ?? $data['data']['tracking_id'] ?? ($shipmentId ? "RTY-{$shipmentId}" : null);

                $order->update([
                    'routely_shipment_id' => $shipmentId,
                    'routely_tracking_number' => $trackingNo,
                    'routely_status' => 'booked',
                    'routely_response' => $data,
                ]);

                return [
                    'success' => true,
                    'message' => 'Shipment dispatched successfully via Routely',
                    'shipment_id' => $shipmentId,
                    'tracking_number' => $trackingNo,
                    'data' => $data,
                ];
            }

            Log::error('Routely shipment creation failed', [
                'order_id' => $order->id,
                'status' => $response->status(),
                'response' => $data,
            ]);

            return [
                'success' => false,
                'message' => $data['message'] ?? 'Failed to create shipment on Routely',
                'errors' => $data['errors'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::error('Routely createShipment exception: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Exception while connecting to Routely: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Track an existing Routely shipment.
     *
     * @return array<string, mixed>
     */
    public function trackShipment(string $trackingNumber): array
    {
        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'message' => 'Routely API key is not configured in .env',
            ];
        }

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'x-api-key' => $this->apiKey,
            ])->timeout(10)->post("{$this->baseUrl}/v1/business/shipments/track", [
                'tracking_number' => $trackingNumber,
            ]);

            return $response->json() ?? ['success' => false, 'message' => 'No tracking response'];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
