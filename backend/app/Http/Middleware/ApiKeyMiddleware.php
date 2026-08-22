<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $rawKey = $request->bearerToken();

        if (! is_string($rawKey) || trim($rawKey) === '') {
            return $this->unauthorized();
        }

        $apiKey = $this->findMatchingKey($rawKey);

        if (! $apiKey || ! $apiKey->organizer) {
            return $this->unauthorized();
        }

        $request->attributes->set('api_key', $apiKey);
        $request->attributes->set('api_key_scopes', $apiKey->scopes ?? []);
        $request->attributes->set('organizer', $apiKey->organizer);

        if ($this->isRateLimited($apiKey)) {
            return $this->rateLimited();
        }

        $apiKey->use($request->ip());

        return $next($request);
    }

    private function findMatchingKey(string $rawKey): ?ApiKey
    {
        $hashIndex = hash('sha256', $rawKey);

        if (ApiKey::where('key_hash_index', $hashIndex)->exists()) {
            return ApiKey::query()
                ->with('organizer')
                ->where('key_hash_index', $hashIndex)
                ->whereNull('revoked_at')
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->first();
        }

        $prefix = $this->extractPrefix($rawKey);

        return ApiKey::query()
            ->with('organizer')
            ->when($prefix !== null, fn ($query) => $query->where('key_prefix', $prefix))
            ->whereNull('revoked_at')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->cursor()
            ->first(fn (ApiKey $candidate): bool => Hash::check($rawKey, $candidate->hashed_key));
    }

    private function extractPrefix(string $rawKey): ?string
    {
        if (! str_contains($rawKey, '|')) {
            return null;
        }

        [$prefix] = explode('|', $rawKey, 2);

        return $prefix !== '' ? $prefix : null;
    }

    private function isRateLimited(ApiKey $apiKey): bool
    {
        if (! $apiKey->rate_limit || ! $apiKey->rate_limit_period) {
            return false;
        }

        $windowSeconds = match ($apiKey->rate_limit_period) {
            'minute', 'minutes' => 60,
            'hour', 'hours' => 3600,
            'day', 'days' => 86400,
            'month', 'months' => 2592000,
            default => (int) $apiKey->rate_limit_period,
        };

        $key = "api_key_rate_limit:{$apiKey->id}:" . (int) (now()->timestamp / $windowSeconds);

        $current = Cache::increment($key, 1, $windowSeconds);

        return $apiKey->isRateLimited($current);
    }

    private function unauthorized(): Response
    {
        return response()->json([
            'message' => 'Unauthenticated. Invalid, revoked, or expired API key.',
        ], 401);
    }

    private function rateLimited(): Response
    {
        return response()->json([
            'message' => 'Rate limit exceeded. Try again later.',
        ], 429);
    }
}
