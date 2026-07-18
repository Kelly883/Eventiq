<?php

return [
    'prefix_length' => 12,
    'secret_length' => 40,
    'rate_limit_per_minute' => env('API_KEY_RATE_LIMIT_PER_MINUTE', 120),
    'authentication_failure_alert_threshold' => env('API_KEY_AUTH_FAILURE_ALERT_THRESHOLD', 10),
    'authentication_failure_decay_seconds' => env('API_KEY_AUTH_FAILURE_DECAY_SECONDS', 300),
    'scopes' => [
        'events:read' => 'Read organizer events.',
        'orders:read' => 'Read orders for organizer events.',
        'tickets:read' => 'Read tickets for organizer events.',
    ],
];
