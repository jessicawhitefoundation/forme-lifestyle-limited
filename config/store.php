<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Store contact details
    |--------------------------------------------------------------------------
    |
    | Shown on the storefront, policy pages, error pages and emails. Change
    | these in .env when the final domain and email addresses are ready.
    |
    */

    'email' => env('STORE_EMAIL', 'hello@shopforme.net'),
    'whatsapp_display' => env('STORE_WHATSAPP_DISPLAY', '+234 702 612 6265'),
    'whatsapp_number' => env('STORE_WHATSAPP_NUMBER', '2347026126265'),

    /*
    |--------------------------------------------------------------------------
    | Nigerian states accepted at checkout
    |--------------------------------------------------------------------------
    */

    'nigeria_states' => [
        'Abia', 'Abuja (FCT)', 'Adamawa', 'Akwa Ibom', 'Anambra', 'Bauchi', 'Bayelsa', 'Benue', 'Borno', 'Cross River',
        'Delta', 'Ebonyi', 'Edo', 'Ekiti', 'Enugu', 'Gombe', 'Imo', 'Jigawa', 'Kaduna', 'Kano',
        'Katsina', 'Kebbi', 'Kogi', 'Kwara', 'Lagos', 'Nasarawa', 'Niger', 'Ogun', 'Ondo', 'Osun',
        'Oyo', 'Plateau', 'Rivers', 'Sokoto', 'Taraba', 'Yobe', 'Zamfara',
    ],

    /*
    |--------------------------------------------------------------------------
    | Countries the store can deliver to
    |--------------------------------------------------------------------------
    |
    | Nigeria is always on and priced by Routely. The overseas list comes from
    | config/countries.php (every country Routely quotes); Store Settings
    | chooses which of them appear at checkout.
    |
    */

    'countries' => [
        'NG' => ['name' => 'Nigeria', 'currency' => 'NGN', 'symbol' => '₦', 'dial' => '+234', 'region_label' => 'State', 'region_required' => true, 'postcode_label' => 'Postcode', 'postcode_required' => false],
    ] + array_map(fn (array $c) => [
        'name' => $c['name'],
        'currency' => $c['currency'],
        'symbol' => $c['symbol'],
        'dial' => $c['dial'],
        'region_label' => $c['region_label'],
        'region_required' => $c['region_required'],
        'postcode_label' => $c['postcode_label'],
        'postcode_required' => $c['postcode'] !== null,
        // Routely prices by country and weight, not city: a central point and a
        // sample postcode stand in until the customer types theirs. Routely
        // needs some postcode, so countries without them send "00000".
        'coordinates' => $c['coordinates'],
        'sample_postcode' => $c['postcode'] ?? '00000',
        'sample_city' => $c['city'],
    ], require __DIR__.'/countries.php'),

    // Overseas parcels are priced by Routely and sent with DHL Express through Routely.
    'international_carrier' => 'DHL Express via Routely',

    // Products have no weights yet, so overseas parcels are estimated from this
    // weight per item (Store Settings can change it). Never less than the minimum.
    'international_item_weight_kg' => 0.7,
    'international_min_weight_kg' => 0.5,

    /*
    | How long items are set aside for a customer who has gone to pay. Others
    | can't buy the last pieces in that time; unpaid holds then lapse on their own.
    */
    'stock_hold_minutes' => (int) env('STOCK_HOLD_MINUTES', 20),

    /*
    |--------------------------------------------------------------------------
    | Lagos areas offered by the delivery estimator
    |--------------------------------------------------------------------------
    */

    'lagos_areas' => [
        'Agege', 'Ajah', 'Alimosho', 'Apapa', 'Badagry', 'Banana Island', 'Chevron', 'Egbeda', 'Epe', 'Festac',
        'Gbagada', 'Ikate', 'Ikeja', 'Ikorodu', 'Ikoyi', 'Ilupeju', 'Ipaja', 'Lekki', 'Magodo', 'Marina',
        'Maryland', 'Obalende', 'Ogba', 'Oniru', 'Osapa', 'Oshodi', 'Sangotedo', 'Surulere', 'Victoria Island', 'Yaba',
    ],

];
