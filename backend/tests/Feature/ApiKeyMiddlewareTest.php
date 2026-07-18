<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\User;
use App\Services\ApiKeyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApiKeyMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_bcrypt_hash_facade_hashes_and_verifies_api_keys(): void
    {
        $rawKey = 'test_prefix|plain-text-secret';
        $hash = Hash::make($rawKey);

        $this->assertTrue(Hash::check($rawKey, $hash));
        $this->assertFalse(Hash::check('wrong-key', $hash));
    }

    public function test_valid_api_key_can_access_v1_events(): void
    {
        [$organizer, $rawKey] = $this->createOrganizerAndRawKey(['events:read']);
        Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Partner Summit',
            'description' => 'API visible event',
            'start_date' => now()->addDay(),
            'end_date' => now()->addDays(2),
            'status' => 'published',
        ]);

        $this->withHeader('Authorization', 'Bearer '.$rawKey)
            ->getJson('/v1/events')
            ->assertOk()
            ->assertJsonFragment(['title' => 'Partner Summit']);
    }

    public function test_invalid_api_key_is_rejected(): void
    {
        $this->withHeader('Authorization', 'Bearer invalid-key')
            ->getJson('/v1/events')
            ->assertUnauthorized();
    }

    public function test_revoked_api_key_is_rejected(): void
    {
        [, $rawKey, $apiKey] = $this->createOrganizerAndRawKey(['events:read']);
        $apiKey->forceFill(['revoked_at' => now()])->save();

        $this->withHeader('Authorization', 'Bearer '.$rawKey)
            ->getJson('/v1/events')
            ->assertUnauthorized();
    }

    public function test_expired_api_key_is_rejected(): void
    {
        [, $rawKey, $apiKey] = $this->createOrganizerAndRawKey(['events:read']);
        $apiKey->forceFill(['expires_at' => now()->subMinute()])->save();

        $this->withHeader('Authorization', 'Bearer '.$rawKey)
            ->getJson('/v1/events')
            ->assertUnauthorized();
    }

    public function test_malformed_authorization_header_is_rejected(): void
    {
        $this->withHeader('Authorization', 'Basic not-a-bearer-token')
            ->getJson('/v1/events')
            ->assertUnauthorized();
    }

    public function test_missing_scope_is_rejected(): void
    {
        [, $rawKey] = $this->createOrganizerAndRawKey(['orders:read']);

        $this->withHeader('Authorization', 'Bearer '.$rawKey)
            ->getJson('/v1/events')
            ->assertForbidden();
    }

    public function test_api_key_service_creates_prefixed_hashed_key_and_revokes_it(): void
    {
        $organizer = $this->createOrganizer();

        ['api_key' => $apiKey, 'raw_key' => $rawKey] = app(ApiKeyService::class)->create(
            $organizer,
            'Developer Portal',
            ['events:read']
        );

        $this->assertStringStartsWith($apiKey->key_prefix.'|', $rawKey);
        $this->assertNotSame($rawKey, $apiKey->hashed_key);
        $this->assertTrue(Hash::check($rawKey, $apiKey->hashed_key));

        app(ApiKeyService::class)->revoke($apiKey->refresh());

        $this->assertNotNull($apiKey->refresh()->revoked_at);
    }

    /**
     * @return array{0: Organizer, 1: string, 2: ApiKey}
     */
    private function createOrganizerAndRawKey(array $scopes): array
    {
        $organizer = $this->createOrganizer();
        $result = app(ApiKeyService::class)->create($organizer, 'Test key', $scopes);

        return [$organizer, $result['raw_key'], $result['api_key']];
    }

    private function createOrganizer(): Organizer
    {
        $user = User::create([
            'name' => 'Organizer User',
            'email' => Str::uuid().'@example.test',
            'password' => 'password',
        ]);

        return Organizer::create([
            'user_id' => $user->id,
            'business_name' => 'Test Organizer',
        ]);
    }
}
