<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tier Image URL Allowlist
    |--------------------------------------------------------------------------
    |
    | Hosts allowed for external tier_image_url values. data: and blob: URIs
    | are always accepted. In production, keep this list restrictive.
    |
    */

    'tier_image_allowed_hosts' => env('TIER_IMAGE_ALLOWED_HOSTS', ''),

    /*
    |--------------------------------------------------------------------------
    | Allow Empty Ticket Tiers
    |--------------------------------------------------------------------------
    |
    | When true, an empty ticketTiers array is allowed even when the event
    | already has tiers. Useful for draft events that may not have tiers yet.
    | When false (default), at least one tier must remain.
    |
    */

    'allow_empty_ticket_tiers' => env('TICKETING_ALLOW_EMPTY_TIERS', false),

];
