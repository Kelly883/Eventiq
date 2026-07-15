<?php

return [
    /*
    |--------------------------------------------------------------------------
    | BAM Ticketing SDK Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration options for the BAM Ticketing SDK,
    | which handles the delivery of tickets via different channels (email, SMS, dashboard).
    |
    */

    'api_key' => env('BAM_TICKETING_API_KEY'),

    'email_from' => env('BAM_TICKETING_EMAIL_FROM', 'tickets@eventiq.com'),

    'sms_from' => env('BAM_TICKETING_SMS_FROM', 'EventIQ'),

    'webhook_secret' => env('BAM_TICKETING_WEBHOOK_SECRET'),
];
