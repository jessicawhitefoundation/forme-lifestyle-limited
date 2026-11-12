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
    | Nigeria is always on and priced by Routely. The others are switched on,
    | and given a flat delivery fee and exchange rate, in Store Settings.
    |
    */

    'countries' => [
        'NG' => ['name' => 'Nigeria', 'currency' => 'NGN', 'symbol' => '₦', 'region_label' => 'State', 'region_required' => true, 'postcode_label' => 'Postcode'],
        // Routely prices overseas parcels by country and weight, not by city, so a
        // central point and a sample postcode stand in until the customer types theirs.
        'GB' => ['name' => 'United Kingdom', 'currency' => 'GBP', 'symbol' => '£', 'region_label' => 'County', 'region_required' => false, 'postcode_label' => 'Postcode', 'coordinates' => '51.5072,-0.1276', 'sample_postcode' => 'SW1A 1AA', 'sample_city' => 'London'],
        'US' => ['name' => 'United States', 'currency' => 'USD', 'symbol' => '$', 'region_label' => 'State', 'region_required' => true, 'postcode_label' => 'ZIP code', 'coordinates' => '40.7128,-74.0060', 'sample_postcode' => '10001', 'sample_city' => 'New York'],
        'CA' => ['name' => 'Canada', 'currency' => 'CAD', 'symbol' => 'C$', 'region_label' => 'Province', 'region_required' => true, 'postcode_label' => 'Postal code', 'coordinates' => '43.6532,-79.3832', 'sample_postcode' => 'M5V 3L9', 'sample_city' => 'Toronto'],
    ],

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
