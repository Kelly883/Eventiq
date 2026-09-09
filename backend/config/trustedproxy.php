<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | This value configures trusted proxies for the application. In production
    | behind Cloudflare, ALB, Render, or Vapor, set TRUSTED_PROXIES in .env
    | to the load-balancer CIDRs or "*" to trust the calling IP. When null,
    | no X-Forwarded-* headers are trusted, so $request->ip() stays at
    | REMOTE_ADDR and rate limits cannot be bypassed via header spoofing.
    |
    | Examples:
    |   TRUSTED_PROXIES="*"                      — trust calling IP (Render/Vapor)
    |   TRUSTED_PROXIES="173.245.48.0/20,103.21.244.0/22" — Cloudflare
    |   TRUSTED_PROXIES="10.0.0.0/8,172.16.0.0/12" — internal ALB
    |
    */
    'proxies' => env('TRUSTED_PROXIES', null),

    'headers' => env('TRUSTED_PROXY_HEADERS', \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR
        | \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST
        | \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT
        | \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO
        | \Illuminate\Http\Request::HEADER_X_FORWARDED_AWS_ELB),
];
