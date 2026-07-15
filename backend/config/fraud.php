<?php

return [

    'sift' => [
        'api_key' => env('SIFT_API_KEY'),
        // Sift's REST API requires both an API key and account ID on
        // most endpoints (https://developers.sift.com/docs/curl/events-api).
        // The Step 40 doc only mentioned SIFT_API_KEY; added this since
        // the integration won't authenticate without it.
        'account_id' => env('SIFT_ACCOUNT_ID'),
        'api_base_url' => env('SIFT_API_BASE_URL', 'https://api.sift.com/v205'),
    ],

    'providers' => [

        'flutterwave' => [
            'public_key' => env('FLUTTERWAVE_PUBLIC_KEY'),
            'secret_key' => env('FLUTTERWAVE_SECRET_KEY'),
            'encryption_key' => env('FLUTTERWAVE_ENCRYPTION_KEY'),
        ],

        'paystack' => [
            'public_key' => env('PAYSTACK_PUBLIC_KEY'),
            'secret_key' => env('PAYSTACK_SECRET_KEY'),
        ],

    ],

    'thresholds' => [

        'high_risk' => 75,
        'medium_risk' => 31,

        'velocity_limit_24h' => 10,
        'velocity_limit_1h' => 3,

        'card_testing_threshold' => 5,

    ],

];
