<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Payment Gateway
    |--------------------------------------------------------------------------
    |
    | This option controls the default payment gateway that will be used to
    | process payments for your application.
    |
    */

    'default' => env('PAYMENT_GATEWAY', 'paystack'),

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | The default currency used for payment transactions.
    |
    */

    'currency' => env('APP_CURRENCY', 'NGN'),

    /*
    |--------------------------------------------------------------------------
    | Payment Gateways Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the settings for each payment gateway used by
    | your application.
    |
    */

    'gateways' => [

        'paystack' => [
            'public_key' => env('PAYSTACK_PUBLIC_KEY'),
            'secret_key' => env('PAYSTACK_SECRET_KEY'),
            'payment_url' => env('PAYSTACK_PAYMENT_URL', 'https://api.paystack.co'),
            'merchant_email' => env('PAYSTACK_MERCHANT_EMAIL'),
        ],

        'flutterwave' => [
            'public_key' => env('FLUTTERWAVE_PUBLIC_KEY'),
            'secret_key' => env('FLUTTERWAVE_SECRET_KEY'),
            'encryption_key' => env('FLUTTERWAVE_ENCRYPTION_KEY'),
            'payment_url' => env('FLUTTERWAVE_PAYMENT_URL', 'https://api.flutterwave.com/v3'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Callback/Webhook URLs
    |--------------------------------------------------------------------------
    |
    | Centralized callback and webhook URLs for payment gateways.
    |
    */

    'callbacks' => [
        'paystack' => [
            'callback_url' => env('PAYSTACK_CALLBACK_URL', '/api/payments/paystack/callback'),
            'webhook_url' => env('PAYSTACK_WEBHOOK_URL', '/api/payments/paystack/webhook'),
        ],
        'flutterwave' => [
            'callback_url' => env('FLUTTERWAVE_CALLBACK_URL', '/api/payments/flutterwave/callback'),
            'webhook_url' => env('FLUTTERWAVE_WEBHOOK_URL', '/api/payments/flutterwave/webhook'),
        ],
    ],

];
