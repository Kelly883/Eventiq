<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\Organizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Covers the ApiKeyMiddleware for the public integration API (/api/v1/…).
 *
 * Key-creation helper
 * -------------------
 * makeKey() creates a matching hashed_key row in the database and returns
 * the raw plaintext key so tests can send it in the Authorization header.
 * This mirrors what ApiKeyService::generate() does, but keeps each test
 * self-contained without coupling to the service layer.
 */
class ApiKeyMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------------
    // Helper
    // -----------------------------------------------------------------------

    /**
     * Create an ApiKey row with a known raw key.
     *
     * @param  array<string, mixed>  $overrides  Overrides applied to the model.
     * @return array{model: ApiKey, raw_key: string}
     */
    private function makeKey(Organizer $organizer, array $overrides = []): array
    {
        $prefix = Str::random(8);
        $secret = Str::random(40);
        $rawKey = "{$prefix}|{$secret}";

        $apiKey = ApiKey::create(array_merge([
            'organizer_id' => $organizer->id,
            'name' => 'Test Key',
            'key_prefix' => $prefix,
            'hashed_key' => Hash::make($rawKey),
            'scopes' => ['events:read'],
            'revoked_at' => null,
            'expires_at' => null,
        ], $overrides));

        return ['model' => $apiKey, 'raw_key' => $rawKey];
    }

    // -----------------------------------------------------------------------
    // Happy path
    // -----------------------------------------------------------------------

    public function test_valid_api_key_authenticates_and_can_access_events(): void
    {
        $organizer = Organizer::factory()->create();
        ['raw_key' => $rawKey] = $this->makeKey($organizer);

        $response = $this->withToken($rawKey)->getJson('/api/v1/events');

        $response->assertOk();
    }

    // -----------------------------------------------------------------------
    // Bcrypt
    // -----------------------------------------------------------------------

    public function test_hash_make_and_check_work_correctly(): void
    {
        $rawKey = 'prefix123|' . Str::random(40);
        $hashed = Hash::make($rawKey);

        $this->assertTrue(Hash::check($rawKey, $hashed), 'Hash::check should return true for matching key');
        $this->assertFalse(Hash::check('wrongkey', $hashed), 'Hash::check should return false for non-matching key');
        $this->assertFalse(Hash::check('', $hashed), 'Hash::check should return false for empty string');
    }

    // -----------------------------------------------------------------------
    // Rejection cases
    // -----------------------------------------------------------------------

    public function test_invalid_api_key_returns_401(): void
    {
        $response = $this->withToken('totally-invalid-key')->getJson('/api/v1/events');

        $response->assertUnauthorized()
            ->assertJsonFragment(['message' => 'Unauthenticated. Invalid, revoked, or expired API key.']);
    }

    public function test_revoked_api_key_returns_401(): void
    {
        $organizer = Organizer::factory()->create();
        ['raw_key' => $rawKey] = $this->makeKey($organizer, ['revoked_at' => now()]);

        $response = $this->withToken($rawKey)->getJson('/api/v1/events');

        $response->assertUnauthorized()
            ->assertJsonFragment(['message' => 'Unauthenticated. Invalid, revoked, or expired API key.']);
    }

    public function test_expired_api_key_returns_401(): void
    {
        $organizer = Organizer::factory()->create();
        ['raw_key' => $rawKey] = $this->makeKey($organizer, ['expires_at' => now()->subSecond()]);

        $response = $this->withToken($rawKey)->getJson('/api/v1/events');

        $response->assertUnauthorized()
            ->assertJsonFragment(['message' => 'Unauthenticated. Invalid, revoked, or expired API key.']);
    }

    public function test_missing_authorization_header_returns_401(): void
    {
        $response = $this->getJson('/api/v1/events');

        $response->assertUnauthorized();
    }

    public function test_malformed_authorization_header_without_bearer_prefix_returns_401(): void
    {
        $response = $this->withHeaders(['Authorization' => 'Basic sometoken'])
            ->getJson('/api/v1/events');

        $response->assertUnauthorized();
    }

    public function test_bearer_token_that_is_only_whitespace_returns_401(): void
    {
        $response = $this->withHeaders(['Authorization' => 'Bearer    '])
            ->getJson('/api/v1/events');

        $response->assertUnauthorized();
    }

    public function test_correct_prefix_but_wrong_secret_returns_401(): void
    {
        $organizer = Organizer::factory()->create();
        ['model' => $apiKey] = $this->makeKey($organizer);

        // Right prefix structure, wrong secret – should not authenticate.
        $fakeRawKey = $apiKey->key_prefix . '|' . Str::random(40);

        $response = $this->withToken($fakeRawKey)->getJson('/api/v1/events');

        $response->assertUnauthorized();
    }

    // -----------------------------------------------------------------------
    // last_used_at is updated on successful auth
    // -----------------------------------------------------------------------

    public function test_last_used_at_is_updated_on_successful_request(): void
    {
        $organizer = Organizer::factory()->create();
        ['model' => $apiKey, 'raw_key' => $rawKey] = $this->makeKey($organizer);

        $this->assertNull($apiKey->last_used_at);

        $this->withToken($rawKey)->getJson('/api/v1/events')->assertOk();

        $this->assertNotNull($apiKey->fresh()->last_used_at);
    }
}
