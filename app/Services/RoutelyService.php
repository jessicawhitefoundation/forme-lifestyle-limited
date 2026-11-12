<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Cache;
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

        // Nigerian States & Capitals
        'abia' => '5.526000,7.489600',
        'umuahia' => '5.526000,7.489600',
        'adamawa' => '9.209400,12.481800',
        'yola' => '9.209400,12.481800',
        'akwa ibom' => '5.037700,7.912800',
        'uyo' => '5.037700,7.912800',
        'anambra' => '6.220900,7.072200',
        'awka' => '6.220900,7.072200',
        'onitsha' => '6.151800,6.785600',
        'bauchi' => '10.315900,9.844200',
        'bayelsa' => '4.926700,6.267600',
        'yenagoa' => '4.926700,6.267600',
        'benue' => '7.732200,8.539100',
        'makurdi' => '7.732200,8.539100',
        'borno' => '11.833300,13.150000',
        'maiduguri' => '11.833300,13.150000',
        'cross river' => '4.975700,8.341700',
        'calabar' => '4.975700,8.341700',
        'delta' => '5.544200,5.760300',
        'asaba' => '6.198400,6.732900',
        'warri' => '5.516700,5.750000',
        'ebonyi' => '6.324900,8.113700',
        'abakaliki' => '6.324900,8.113700',
        'edo' => '6.335000,5.603700',
        'benin' => '6.335000,5.603700',
        'ekiti' => '7.621100,5.221500',
        'ado-ekiti' => '7.621100,5.221500',
        'enugu' => '6.458400,7.546400',
        'abuja' => '9.076500,7.398600',
        'fct' => '9.076500,7.398600',
        'gombe' => '10.289700,11.167300',
        'imo' => '5.485200,7.035000',
        'owerri' => '5.485200,7.035000',
        'jigawa' => '11.756200,9.339000',
        'dutse' => '11.756200,9.339000',
        'kaduna' => '10.510500,7.416500',
        'kano' => '12.002200,8.592000',
        'katsina' => '12.990800,7.601800',
        'kebbi' => '12.453900,4.197500',
        'birnin kebbi' => '12.453900,4.197500',
        'kogi' => '7.802300,6.733300',
        'lokoja' => '7.802300,6.733300',
        'kwara' => '8.479900,4.541800',
        'ilorin' => '8.479900,4.541800',
        'nasarawa' => '8.493200,8.515300',
        'lafia' => '8.493200,8.515300',
        'niger' => '9.583600,6.546300',
        'minna' => '9.583600,6.546300',
        'ogun' => '7.147500,3.361900',
        'abeokuta' => '7.147500,3.361900',
        'ondo' => '7.257100,5.205800',
        'akure' => '7.257100,5.205800',
        'osun' => '7.782700,4.541800',
        'osogbo' => '7.782700,4.541800',
        'oyo' => '7.377500,3.947000',
        'ibadan' => '7.377500,3.947000',
        'plateau' => '9.896500,8.858300',
        'jos' => '9.896500,8.858300',
        'rivers' => '4.815600,7.049800',
        'port harcourt' => '4.815600,7.049800',
        'sokoto' => '13.005900,5.247600',
        'taraba' => '8.893700,11.359600',
        'jalingo' => '8.893700,11.359600',
        'yobe' => '11.747000,11.960800',
        'damaturu' => '11.747000,11.960800',
        'zamfara' => '12.162800,6.661400',
        'gusau' => '12.162800,6.661400',
    ];

    /**
     * Default state capitals mapping for accurate interstate carrier rating
     *
     * @var array<string, string>
     */
    protected static array $stateCapitals = [
        'Abia' => 'Umuahia',
        'Adamawa' => 'Yola',
        'Akwa Ibom' => 'Uyo',
        'Anambra' => 'Awka',
        'Bauchi' => 'Bauchi',
        'Bayelsa' => 'Yenagoa',
        'Benue' => 'Makurdi',
        'Borno' => 'Maiduguri',
        'Cross River' => 'Calabar',
        'Delta' => 'Asaba',
        'Ebonyi' => 'Abakaliki',
        'Edo' => 'Benin',
        'Ekiti' => 'Ado-Ekiti',
        'Enugu' => 'Enugu',
        'Abuja' => 'Abuja',
        'Gombe' => 'Gombe',
        'Imo' => 'Owerri',
        'Jigawa' => 'Dutse',
        'Kaduna' => 'Kaduna',
        'Kano' => 'Kano',
        'Katsina' => 'Katsina',
        'Kebbi' => 'Birnin Kebbi',
        'Kogi' => 'Lokoja',
        'Kwara' => 'Ilorin',
        'Lagos' => 'Ikeja',
        'Nasarawa' => 'Lafia',
        'Niger' => 'Minna',
        'Ogun' => 'Abeokuta',
        'Ondo' => 'Akure',
        'Osun' => 'Osogbo',
        'Oyo' => 'Ibadan',
        'Plateau' => 'Jos',
        'Rivers' => 'Port Harcourt',
        'Sokoto' => 'Sokoto',
        'Taraba' => 'Jalingo',
        'Yobe' => 'Damaturu',
        'Zamfara' => 'Gusau',
    ];

    /**
     * Normalize Nigerian state name to what Routely expects.
     */
    public static function normalizeState(?string $state): string
    {
        $s = strtolower(trim((string) $state));
        if ($s === 'fct' || str_contains($s, 'abuja') || str_contains($s, 'capital')) {
            return 'Abuja';
        }
        if (str_contains($s, 'lagos')) {
            return 'Lagos';
        }
        if (str_contains($s, 'akwa')) {
            return 'Akwa Ibom';
        }
        if (str_contains($s, 'cross')) {
            return 'Cross River';
        }

        return ucwords($s);
    }

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
        $toState = self::normalizeState($params['state'] ?? 'Lagos');
        $defaultCity = self::$stateCapitals[$toState] ?? $toState;

        $rawCity = trim((string) ($params['city'] ?? ''));
        $lagosAreas = ['lekki', 'ikeja', 'victoria island', 'vi', 'yaba', 'surulere', 'ajah', 'ikoyi', 'lagos', 'maryland', 'gbagada', 'magodo', 'festac'];
        $rawCityLower = strtolower($rawCity);
        $isLagosArea = in_array($rawCityLower, $lagosAreas);

        $toCity = ($rawCity !== '' && ($toState === 'Lagos' || ! $isLagosArea))
            ? $rawCity
            : ($toState === 'Lagos' ? 'Lekki' : $defaultCity);

        $rawAddress = trim((string) ($params['address'] ?? ''));
        $toAddress = $rawAddress !== '' ? $rawAddress : "{$toCity}, {$toState}";
        $itemValue = (float) ($params['item_value'] ?? 25000);
        $weight = (float) ($params['weight'] ?? 1.5);

        $fromAddress = $this->sender['address'] ?? 'Lekki Phase 1, Lagos';
        $fromCoords = $this->sender['coordinates'] ?? '6.4426,3.4833';
        $toCoords = $params['to_coordinates'] ?? self::resolveCoordinates($toAddress, $toCity, $toState);

        $isIntracity = strcasecmp(trim($toState), 'lagos') === 0;

        $cacheKey = 'routely_fee_'.md5(implode('|', [strtolower($toAddress), strtolower($toCity), strtolower($toState), round($itemValue, -3), $weight]));
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

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
                    'to' => $toAddress,
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
                    'to' => $toAddress,
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
            ])->timeout(10)->post("{$this->baseUrl}/v1/business/shipments/fee", $payload);

            if ($response->successful() && ($response->json('success') === true)) {
                $rates = $response->json('data.rates', []);
                $selectedRate = $rates[0] ?? null;

                $quote = [
                    'success' => true,
                    'rate' => $selectedRate ? (float) $selectedRate['selling_price'] : ($isIntracity ? 3000.0 : 6500.0),
                    'carrier' => $selectedRate['carrier_name'] ?? 'Routely Standard',
                    'delivery_time' => $selectedRate['delivery_time'] ?? ($isIntracity ? '1-2 business days' : '3-5 business days'),
                    'cachekey' => $response->json('data.cachekey'),
                    'all_rates' => $rates,
                ];

                if ($selectedRate) {
                    Cache::put($cacheKey, $quote, now()->addMinutes(15));
                }

                return $quote;
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
     * Routely's price for sending a parcel abroad. Routely prices by country and
     * weight; this picks DHL Express (2–4 working days) when offered, otherwise
     * the cheapest express service. Quotes don't book or charge anything.
     *
     * @param  array{name: string, coordinates: string, sample_postcode: string, sample_city: string}  $country
     * @param  array{address?: ?string, city?: ?string, state?: ?string, postcode?: ?string, weight: float, item_value: float}  $parcel
     * @return array{success: bool, rate: ?float, carrier: ?string, delivery_time: ?string, error?: string}
     */
    public function getInternationalFee(array $country, array $parcel): array
    {
        $failed = fn (string $error) => ['success' => false, 'rate' => null, 'carrier' => null, 'delivery_time' => null, 'error' => $error];

        if (empty($this->apiKey)) {
            return $failed('Routely is not configured.');
        }

        $weight = round(max((float) config('store.international_min_weight_kg', 0.5), $parcel['weight']), 1);
        $itemValue = (float) $parcel['item_value'];
        $postcode = trim((string) ($parcel['postcode'] ?? '')) ?: $country['sample_postcode'];
        $city = trim((string) ($parcel['city'] ?? '')) ?: $country['sample_city'];

        // The price doesn't depend on the street or city, so one cached quote per
        // country, weight and rough order value serves every customer for a while.
        $cacheKey = 'routely_intl_fee_'.md5(implode('|', [$country['name'], $weight, round($itemValue, -4)]));
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        $payload = [
            'from' => $this->sender['address'] ?? 'Lekki Phase 1, Lagos',
            'from_state' => 'Lagos',
            'from_city' => 'Lekki',
            'from_country' => 'Nigeria',
            'from_coordinates' => $this->sender['coordinates'] ?? '6.4426,3.4833',
            'to' => trim((string) ($parcel['address'] ?? '')) ?: "{$city}, {$country['name']}",
            'to_city' => $city,
            'to_state' => trim((string) ($parcel['state'] ?? '')) ?: $city,
            'to_country' => $country['name'],
            'to_postal_code' => $postcode,
            'to_coordinates' => $country['coordinates'],
            'package_type' => 'package',
            'weight' => $weight,
            'has_packaging' => false,
            'item_value' => $itemValue,
            'has_insurance' => false,
            'has_pickup' => true,
            'service_station' => 82, // Required by Routely for Lagos origin
        ];

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'x-api-key' => $this->apiKey,
            ])->timeout(15)->post("{$this->baseUrl}/v1/business/shipments/fee", $payload);
        } catch (\Throwable $e) {
            Log::warning("Routely overseas quote to {$country['name']} could not be reached: {$e->getMessage()}");

            return $failed($e->getMessage());
        }

        $express = collect($response->json('data.rates.express') ?? [])
            ->filter(fn ($rate) => is_array($rate) && is_numeric($rate['selling_price'] ?? null) && empty($rate['is_restricted_country']));

        if (! $response->successful() || $express->isEmpty()) {
            Log::warning("Routely gave no overseas quote to {$country['name']}", [
                'status' => $response->status(),
                'message' => $response->json('message'),
            ]);

            return $failed((string) ($response->json('message') ?? 'No express rate offered.'));
        }

        $chosen = $express->first(fn ($rate) => str_contains(strtolower((string) ($rate['carrier_name'] ?? '')), 'dhl'))
            ?? $express->sortBy(fn ($rate) => (float) $rate['selling_price'])->first();

        // "Premium - DHL Express" → "DHL Express"
        $carrier = trim((string) preg_replace('/^[^-]*-\s*/', '', (string) ($chosen['carrier_name'] ?? 'Express')));

        $quote = [
            'success' => true,
            'rate' => round((float) $chosen['selling_price'], 2),
            'carrier' => $carrier !== '' ? $carrier : 'Express',
            'delivery_time' => $chosen['delivery_time'] ?? null,
        ];

        Cache::put($cacheKey, $quote, now()->addHours(6));

        return $quote;
    }

    /**
     * Create a shipment with Routely for a placed order.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function createShipment(Order $order, array $options = []): array
    {
        $toState = self::normalizeState($order->shipping_state ?: 'Lagos');
        $defaultCity = self::$stateCapitals[$toState] ?? $toState;

        $rawCity = trim((string) ($order->shipping_city ?: ''));
        $lagosAreas = ['lekki', 'ikeja', 'victoria island', 'vi', 'yaba', 'surulere', 'ajah', 'ikoyi', 'lagos', 'maryland', 'gbagada', 'magodo', 'festac'];
        $rawCityLower = strtolower($rawCity);
        $isLagosArea = in_array($rawCityLower, $lagosAreas);

        $toCity = ($rawCity !== '' && ($toState === 'Lagos' || ! $isLagosArea))
            ? $rawCity
            : ($toState === 'Lagos' ? 'Lekki' : $defaultCity);

        $rawAddress = trim((string) ($order->shipping_address ?: ''));
        $toAddress = $rawAddress !== '' ? $rawAddress : "{$toCity}, {$toState}";
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

        $order->loadMissing('items');

        // What the courier charges, even when the customer got free delivery.
        $courierFee = (float) ($order->routely_fee ?: $order->shipping_fee);

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
                            'email' => $this->sender['email'] ?? 'hello@shopforme.net',
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
                    'total_amount' => $courierFee > 0 ? $courierFee : 2500.0,
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
                        'email' => $this->sender['email'] ?? 'hello@shopforme.net',
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
                    'total_amount' => $courierFee > 0 ? $courierFee : 5000.0,
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
