<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Global security headers — CSP for images, HSTS, etc.
 *
 * Previously CSP was only on banner upload response (EventController@uploadBanner).
 * Now applied globally so every API response (and thus every image URL rendered
 * by the SPA) is covered, preventing an attacker from tricking the SPA into
 * loading an external banner URL.
 *
 * img-src 'self' + storage hosts (S3, APP_URL) + data: for FileReader previews.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Build allowlist from configured storage hosts
        $allowedHosts = array_filter([
            parse_url(config('app.url'), PHP_URL_HOST),
            parse_url(config('filesystems.disks.s3.url') ?? '', PHP_URL_HOST),
            parse_url(config('filesystems.disks.s3.endpoint') ?? '', PHP_URL_HOST),
            parse_url(env('AWS_URL', ''), PHP_URL_HOST),
            // Frontend host for completeness (in case SPA serves images)
            parse_url(config('app.frontend_url', env('FRONTEND_URL', '')), PHP_URL_HOST),
        ]);
        $allowedHosts = array_unique($allowedHosts);
        $cspHosts = implode(' ', array_map(fn($h) => "https://$h", $allowedHosts));

        // Only set if not already set (banner endpoint sets a more specific one)
        if (!$response->headers->has('Content-Security-Policy')) {
            $response->headers->set('Content-Security-Policy', "img-src 'self' $cspHosts data: blob:; default-src 'self'; frame-ancestors 'none'");
        }
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // HSTS only in prod over HTTPS
        if (app()->environment('production') && $request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
