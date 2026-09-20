<?php

use Illuminate\Support\Str;

return [

    /*
    |----------------------------------------------------------------------
    | Default: cookie driver
    |----------------------------------------------------------------------
    | The 'database' driver CANNOT be used with this app: the `sessions`
    | table is the custom bearer-token store (app/Models/Session.php),
    | whose schema (token, expiresAt, revokedAt) has no `payload` column.
    | With the database driver every request 500s on session write.
    | Production uses signed stateless cookies (see .env.example) — this
    | default keeps any environment without an explicit SESSION_DRIVER
    | safe instead of crashing.
    */
    'driver' => env('SESSION_DRIVER', 'cookie'),

    'lifetime' => (int) env('SESSION_LIFETIME', 120),

    'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', false),

    'encrypt' => env('SESSION_ENCRYPT', false),

    'files' => storage_path('framework/sessions'),

    'connection' => env('SESSION_CONNECTION'),

    'table' => env('SESSION_TABLE', 'sessions'),

    'store' => env('SESSION_STORE'),

    'lottery' => [2, 100],

    'cookie' => env(
        'SESSION_COOKIE',
        Str::slug(env('APP_NAME', 'laravel'), '_').'_session'
    ),

    'path' => env('SESSION_PATH', '/'),

    'domain' => env('SESSION_DOMAIN'),

    'secure' => env('SESSION_SECURE_COOKIE'),

    'http_only' => env('SESSION_HTTP_ONLY', true),

    'same_site' => env('SESSION_SAME_SITE', 'lax'),

    'partitioned' => env('SESSION_PARTITIONED_COOKIE', false),

];
