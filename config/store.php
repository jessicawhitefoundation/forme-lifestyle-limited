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
    | Lagos areas offered by the delivery estimator
    |--------------------------------------------------------------------------
    */

    'lagos_areas' => [
        'Agege', 'Ajah', 'Alimosho', 'Apapa', 'Badagry', 'Banana Island', 'Chevron', 'Egbeda', 'Epe', 'Festac',
        'Gbagada', 'Ikate', 'Ikeja', 'Ikorodu', 'Ikoyi', 'Ilupeju', 'Ipaja', 'Lekki', 'Magodo', 'Marina',
        'Maryland', 'Obalende', 'Ogba', 'Oniru', 'Osapa', 'Oshodi', 'Sangotedo', 'Surulere', 'Victoria Island', 'Yaba',
    ],

];
