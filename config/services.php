<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'paystack' => [
        'secret_key' => env('PAYSTACK_SECRET_KEY'),
        'public_key' => env('PAYSTACK_PUBLIC_KEY'),
    ],

    'routely' => [
        'base_url' => env('ROUTELY_BASE_URL', 'https://server.routely.co/api'),
        'api_key' => env('ROUTELY_API_KEY', env('ROUTELY_TEST_KEY')),
        'env' => env('ROUTELY_ENV', 'test'),
        'sender' => [
            'name' => env('ROUTELY_SENDER_NAME', 'FORMÉ Lagos'),
            'phone' => env('ROUTELY_SENDER_PHONE', '07026126265'),
            'email' => env('ROUTELY_SENDER_EMAIL', 'hello@forme.ng'),
            'address' => env('ROUTELY_SENDER_ADDRESS', 'Lekki Phase 1, Lagos'),
            'city' => env('ROUTELY_SENDER_CITY', 'Lekki'),
            'state' => env('ROUTELY_SENDER_STATE', 'Lagos'),
            'coordinates' => env('ROUTELY_SENDER_COORDINATES', '6.4426,3.4833'),
        ],
    ],

];
