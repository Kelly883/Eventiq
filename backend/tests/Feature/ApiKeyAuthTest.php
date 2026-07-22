<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\Event;
use App\Models\Organizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApiKeyAuthTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Create an organizer with a known raw API key and return both.
     *
     * @param  array<string, mixed>  $keyOverrides
     * @return array{organizer: Organizer, apiKey: ApiKey, rawKey: string}
     */
    private function makeOrganizerWithKey(array $keyOverrides = []): array
    {
        $prefix = 'ek_test_' . Str::random(8);
        $secret = Str::random(32);
        $rawKey = "{$prefix}|{$secret}";

        $organizer = Organizer::factory()->create();
        $apiKey = ApiKey::factory()->for($organizer)->create(array_merge([
            'key_prefix' => $prefix,
            'hashed_key' => Hash::make($rawKey),
            'scopes' => ['events:read'],
        ], $keyOverrides));

        return compact('organizer', 'apiKey', 'rawKey');
    }

    // -------------------------------------------------------------------------
    // /api/v1/events – API key auth flow
    // -------------------------------------------------------------------------

    public function test_valid_api_key_authenticates_to_v1_events(): void
    {
        ['organizer' => $organizer, 'rawKey' => $rawKey] = $this->makeOrganizerWithKey();

        // Seed one event for this organizer so the response body is verifiable.
        Event::factory()->for($organizer)->create(['title' => 'Test Event']);

        $response = $this->withToken($rawKey)->getJson('/api/v1/events');

        $response->assertOk();
        $response->assertJsonFragment(['title' => 'Test Event']);
    }

    public function test_invalid_api_key_returns_401(): void
    {
        $response = $this->withToken('invalid-key-does-not-exist')->getJson('/api/v1/events');

        $response->assertUnauthorized();
    }

    public function test_revoked_api_key_returns_401(): void
    {
        ['rawKey' => $rawKey] = $this->makeOrganizerWithKey(['revoked_at' => now()->subMinute()]);

        $response = $this->withToken($rawKey)->getJson('/api/v1/events');

        $response->assertUnauthorized();
    }

    public function test_expired_api_key_returns_401(): void
    {
        ['rawKey' => $rawKey] = $this->makeOrganizerWithKey(['expires_at' => now()->subDay()]);

        $response = $this->withToken($rawKey)->getJson('/api/v1/events');

        $response->assertUnauthorized();
    }

    public function test_missing_authorization_header_returns_401(): void
    {
        $response = $this->getJson('/api/v1/events');

        $response->assertUnauthorized();
    }

    public function test_malformed_authorization_header_returns_401(): void
    {
        $response = $this->withHeaders(['Authorization' => 'NotBearer abc123'])
            ->getJson('/api/v1/events');

        $response->assertUnauthorized();
    }

    public function test_events_scope_restricts_organizer_to_own_events(): void
    {
        ['organizer' => $organizer, 'rawKey' => $rawKey] = $this->makeOrganizerWithKey();

        $ownEvent = Event::factory()->for($organizer)->create(['title' => 'Own Event']);

        // Create another organizer's event that should NOT appear in the response.
        $otherOrganizer = Organizer::factory()->create();
        Event::factory()->for($otherOrganizer)->create(['title' => 'Other Event']);

        $response = $this->withToken($rawKey)->getJson('/api/v1/events');

        $response->assertOk();
        $response->assertJsonFragment(['title' => 'Own Event']);
        $response->assertJsonMissing(['title' => 'Other Event']);
    }
}
