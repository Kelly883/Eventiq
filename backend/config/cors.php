<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Applies to API routes and Sanctum's stateful CSRF cookie route so the
    | Vite-served React frontend (localhost:5173 in dev) can call this API.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // Comma-separated list in CORS_ALLOWED_ORIGINS, e.g.
    // "http://localhost:5173,https://app.eventiq.example"
    'allowed_origins' => array_filter(explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:5173'))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // true only if the frontend sends cookies/Authorization headers (Sanctum SPA auth).
    'supports_credentials' => true,

];
