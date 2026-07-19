<?php

namespace App\Features\ApiKeys\Services;

use App\Models\ApiKey;
use App\Models\Organizer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Generates and manages API keys for ApiKeyMiddleware to verify.
 * Key format: {prefix}|{secret}. The prefix is always present (never
 * null) - ApiKeyMiddleware falls back to a full-table Hash::check() scan
 * for prefix-less keys, which degrades as the table grows. Always
 * generating a prefix here keeps every key on the fast, indexed lookup
 * path.
 */
class ApiKeyService
{
    /**
     * @return array{model: ApiKey, raw_key: string} The raw key is only
     *   ever available here, at creation time - only its bcrypt hash is
     *   stored, so it can never be retrieved again after this call
     *   returns. Same UX as GitHub/Stripe personal access tokens.
     */
    public function generate(Organizer $organizer, string $name, array $scopes = [], ?\DateTimeInterface $expiresAt = null): array
    {
        $prefix = Str::random(8);
        $secret = Str::random(40);
        $rawKey = "{$prefix}|{$secret}";

        $apiKey = ApiKey::create([
            'organizer_id' => $organizer->id,
            'name' => $name,
            'key_prefix' => $prefix,
            'hashed_key' => Hash::make($rawKey),
            'scopes' => $scopes,
            'expires_at' => $expiresAt,
        ]);

        return ['model' => $apiKey, 'raw_key' => $rawKey];
    }

    /**
     * Revoke a key. Sets revoked_at rather than deleting the row, so it
     * still shows in the organizer's key list (as revoked) and preserves
     * the audit trail of last_used_at / created_at.
     */
    public function revoke(ApiKey $apiKey): void
    {
        $apiKey->update(['revoked_at' => now()]);
    }
}
