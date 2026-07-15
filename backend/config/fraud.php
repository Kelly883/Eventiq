<?php

return [
    'sift' => [
        'api_key' => env('SIFT_API_KEY'),
        'account_id' => env('SIFT_ACCOUNT_ID'),
        'api_base_url' => env('SIFT_API_BASE_URL', 'https://api.sift.com/v3'),
    ],
    'stripe' => [
        'api_key' => env('STRIPE_API_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],
    'thresholds' => [
        'high_risk' => env('HIGH_RISK_THRESHOLD', 75),
        'medium_risk' => env('MEDIUM_RISK_THRESHOLD', 31),
        'velocity_limit_24h' => env('VELOCITY_LIMIT_24H', 10),
        'velocity_limit_1h' => env('VELOCITY_LIMIT_1H', 3),
        'card_testing_threshold' => env('CARD_TESTING_THRESHOLD', 5),
    ],
];
