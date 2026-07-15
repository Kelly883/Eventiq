<?php

/**
 * Config for ticket delivery (email/SMS/dashboard).
 *
 * NOTE: the source doc for this step asked to `composer require
 * bam-ticketing-sdk`. That package does not exist for PHP/Composer -
 * `bam-ticketing-sdk` is a real npm package, but for B.A.M Ticketing, an
 * unrelated NFT/blockchain ticketing platform with no email/SMS delivery
 * API. It was not installed. Email uses Laravel's built-in Mail; SMS uses
 * Termii (a real, commonly-used Nigerian SMS API - fits this app's NGN/
 * Paystack/Flutterwave context) via Laravel's HTTP client, consistent with
 * how Paystack/Flutterwave were integrated.
 */

return [

    'email' => [
        'from_address' => env('TICKET_DELIVERY_EMAIL_FROM', env('MAIL_FROM_ADDRESS')),
        'from_name' => env('TICKET_DELIVERY_EMAIL_FROM_NAME', env('MAIL_FROM_NAME', 'EventIQ')),
    ],

    'sms' => [
        'provider' => env('TICKET_DELIVERY_SMS_PROVIDER', 'termii'),
        'api_key' => env('TERMII_API_KEY'),
        'sender_id' => env('TICKET_DELIVERY_SMS_FROM'),
        'base_url' => env('TERMII_BASE_URL', 'https://api.ng.termii.com'),
        // 'generic' (non-DND, promotional only) or 'dnd' (transactional -
        // required for OTP/ticket delivery to reach DND-listed numbers).
        'channel' => env('TERMII_CHANNEL', 'dnd'),
    ],

    // Shared secret WE define and expect delivery-status webhook callers
    // to send back (e.g. as a query param or header we configure on our
    // end) - not a signature scheme documented by any specific provider,
    // since Termii's public docs don't describe one for SMS DLR callbacks.
    'webhook_secret' => env('TICKET_DELIVERY_WEBHOOK_SECRET'),

];
