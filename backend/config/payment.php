<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    */
    'currency' => env('APP_CURRENCY', 'NGN'),

    /*
    |--------------------------------------------------------------------------
    | Registered Payment Providers
    |--------------------------------------------------------------------------
    | Used by PaymentService / PaymentServiceProvider to know which gateways
    | are available. Add a new key here when a new gateway is integrated.
    */
    'providers' => ['paystack', 'flutterwave'],

    /*
    |--------------------------------------------------------------------------
    | Paystack
    |--------------------------------------------------------------------------
    */
    'paystack' => [
        'public_key' => env('PAYSTACK_PUBLIC_KEY'),
        'secret_key' => env('PAYSTACK_SECRET_KEY'),
        'base_url' => env('PAYSTACK_BASE_URL', 'https://api.paystack.co'),
        'callback_url' => env('PAYSTACK_CALLBACK_URL', env('APP_URL').'/api/payments/paystack/callback'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Flutterwave
    |--------------------------------------------------------------------------
    */
    'flutterwave' => [
        'public_key' => env('FLUTTERWAVE_PUBLIC_KEY'),
        'secret_key' => env('FLUTTERWAVE_SECRET_KEY'),
        'encryption_key' => env('FLUTTERWAVE_ENCRYPTION_KEY'),
        'base_url' => env('FLUTTERWAVE_BASE_URL', 'https://api.flutterwave.com/v3'),
        'callback_url' => env('FLUTTERWAVE_CALLBACK_URL', env('APP_URL').'/api/payments/flutterwave/callback'),
    ],

];
