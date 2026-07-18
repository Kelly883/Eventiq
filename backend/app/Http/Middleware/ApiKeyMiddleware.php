<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Services\ApiKeyService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    public function __construct(private readonly ApiKeyService $apiKeyService)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $rawKey = $request->bearerToken();

        if (! is_string($rawKey) || trim($rawKey) === '') {
            $this->apiKeyService->recordAuthenticationFailure(reason: 'missing_bearer_token');

            return $this->unauthorized();
        }

        $apiKey = $this->findMatchingKey($rawKey);

        if (! $apiKey || ! $apiKey->organizer) {
            $this->apiKeyService->recordAuthenticationFailure($this->extractPrefix($rawKey));

            return $this->unauthorized();
        }

        $this->apiKeyService->recordAuthenticationSuccess($apiKey);

        $request->attributes->set('api_key', $apiKey);
        $request->attributes->set('api_key_scopes', $apiKey->scopes ?? []);
        $request->attributes->set('organizer', $apiKey->organizer);

        return $next($request);
    }

    private function findMatchingKey(string $rawKey): ?ApiKey
    {
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

    private function unauthorized(): Response
    {
        return response()->json([
            'message' => 'Unauthenticated. Invalid, revoked, or expired API key.',
        ], 401);
    }
}
