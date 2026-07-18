<?php

namespace App\Services;

use App\Features\Compliance\Services\AuditLogService;
use App\Models\ApiKey;
use App\Models\Organizer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ApiKeyService
{
    public function __construct(private readonly AuditLogService $auditLogService)
    {
    }

    /**
     * @return array{api_key: ApiKey, raw_key: string}
     */
    public function create(Organizer $organizer, string $name, array $scopes = [], ?\DateTimeInterface $expiresAt = null): array
    {
        $this->validateScopes($scopes);

        $prefix = $this->generateUniquePrefix();
        $rawKey = $prefix.'|'.Str::random((int) config('api-keys.secret_length', 40));

        $apiKey = ApiKey::create([
            'organizer_id' => $organizer->id,
            'name' => $name,
            'key_prefix' => $prefix,
            'hashed_key' => Hash::make($rawKey),
            'scopes' => array_values($scopes),
            'expires_at' => $expiresAt,
        ]);

        $this->auditLogService->log('api_key.created', 'api_key', $apiKey->id, [
            'organizer_id' => $organizer->id,
            'name' => $name,
            'key_prefix' => $prefix,
            'scopes' => array_values($scopes),
            'expires_at' => $expiresAt?->format(DATE_ATOM),
        ], $organizer->user_id);

        return ['api_key' => $apiKey, 'raw_key' => $rawKey];
    }

    public function revoke(ApiKey $apiKey, ?int $revokedByUserId = null): ApiKey
    {
        if ($apiKey->revoked_at === null) {
            $apiKey->forceFill(['revoked_at' => now()])->save();
        }

        $this->auditLogService->log('api_key.revoked', 'api_key', $apiKey->id, [
            'organizer_id' => $apiKey->organizer_id,
            'key_prefix' => $apiKey->key_prefix,
        ], $revokedByUserId ?? $apiKey->organizer?->user_id);

        return $apiKey;
    }

    public function recordAuthenticationSuccess(ApiKey $apiKey): void
    {
        $apiKey->forceFill(['last_used_at' => now()])->save();

        $this->auditLogService->log('api_key.authenticated', 'api_key', $apiKey->id, [
            'organizer_id' => $apiKey->organizer_id,
            'key_prefix' => $apiKey->key_prefix,
        ], $apiKey->organizer?->user_id);
    }

    public function recordAuthenticationFailure(?string $keyPrefix = null, string $reason = 'invalid'): void
    {
        $monitorKey = 'api-key-auth-failures:'.($keyPrefix ?? request()?->ip() ?? 'unknown');
        RateLimiter::hit($monitorKey, (int) config('api-keys.authentication_failure_decay_seconds', 300));

        $this->auditLogService->log('api_key.authentication_failed', 'api_key', null, [
            'key_prefix' => $keyPrefix,
            'reason' => $reason,
            'attempts' => RateLimiter::attempts($monitorKey),
        ]);

        if (RateLimiter::attempts($monitorKey) >= (int) config('api-keys.authentication_failure_alert_threshold', 10)) {
            Log::warning('Repeated API key authentication failures detected.', [
                'key_prefix' => $keyPrefix,
                'reason' => $reason,
                'attempts' => RateLimiter::attempts($monitorKey),
            ]);
        }
    }

    private function generateUniquePrefix(): string
    {
        do {
            $prefix = Str::lower(Str::random((int) config('api-keys.prefix_length', 12)));
        } while (ApiKey::where('key_prefix', $prefix)->exists());

        return $prefix;
    }

    private function validateScopes(array $scopes): void
    {
        $allowedScopes = array_keys(config('api-keys.scopes', []));
        $unknownScopes = array_values(array_diff($scopes, $allowedScopes));

        if ($unknownScopes !== []) {
            throw new InvalidArgumentException('Unknown API key scopes: '.implode(', ', $unknownScopes));
        }
    }
}
