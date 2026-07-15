<?php

return [
    'sift' => [
        'api_key' => env('SIFT_API_KEY'),
        'account_id' => env('SIFT_ACCOUNT_ID'),
        'api_base_url' => env('SIFT_API_BASE_URL', 'https://api.sift.com/v3'),
    ],
    'paystack' => [
        'secret_key' => env('PAYSTACK_SECRET_KEY'),
        'webhook_secret' => env('PAYSTACK_WEBHOOK_SECRET'),
    ],
    'flutterwave' => [
        'secret_key' => env('FLUTTERWAVE_SECRET_KEY'),
        'encryption_key' => env('FLUTTERWAVE_ENCRYPTION_KEY'),
        'webhook_secret' => env('FLUTTERWAVE_WEBHOOK_SECRET'),
    ],
    'thresholds' => [
        'high_risk' => env('HIGH_RISK_THRESHOLD', 75),
        'medium_risk' => env('MEDIUM_RISK_THRESHOLD', 31),
        'velocity_limit_24h' => env('VELOCITY_LIMIT_24H', 10),
        'velocity_limit_1h' => env('VELOCITY_LIMIT_1H', 3),
        'card_testing_threshold' => env('CARD_TESTING_THRESHOLD', 5),
        'max_tickets_per_transaction' => env('MAX_TICKETS_PER_TRANSACTION', 10),
        'max_transactions_per_device' => env('MAX_TRANSACTIONS_PER_DEVICE', 5),
        'max_transactions_per_ip' => env('MAX_TRANSACTIONS_PER_IP', 10),
    ],
];
