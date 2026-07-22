<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\Organizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class GraphQLAuthTest extends TestCase
{
    use RefreshDatabase;

    private const EVENTS_QUERY = '{ events { id title } }';

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * @param  array<string, mixed>  $keyOverrides
     * @return array{organizer: Organizer, apiKey: ApiKey, rawKey: string}
     */
    private function makeOrganizerWithKey(array $keyOverrides = []): array
    {
        $prefix = 'ek_gql_' . Str::random(8);
        $secret = Str::random(32);
        $rawKey = "{$prefix}|{$secret}";

        $organizer = Organizer::factory()->create();
        $apiKey = ApiKey::factory()->for($organizer)->create(array_merge([
            'key_prefix' => $prefix,
            'hashed_key' => Hash::make($rawKey),
            'scopes' => ['events:read', 'orders:read', 'tickets:read'],
        ], $keyOverrides));

        return compact('organizer', 'apiKey', 'rawKey');
    }

    // -------------------------------------------------------------------------
    // /graphql – authentication
    // -------------------------------------------------------------------------

    public function test_valid_key_with_events_scope_can_query_events(): void
    {
        ['rawKey' => $rawKey] = $this->makeOrganizerWithKey(['scopes' => ['events:read']]);

        $response = $this->withToken($rawKey)
            ->postJson('/graphql', ['query' => self::EVENTS_QUERY]);

        $response->assertOk();
        $response->assertJsonStructure(['data' => ['events']]);
    }

    public function test_invalid_key_on_graphql_returns_401(): void
    {
        $response = $this->withToken('this-is-not-a-real-key')
            ->postJson('/graphql', ['query' => self::EVENTS_QUERY]);

        $response->assertUnauthorized();
    }

    public function test_missing_token_on_graphql_returns_401(): void
    {
        $response = $this->postJson('/graphql', ['query' => self::EVENTS_QUERY]);

        $response->assertUnauthorized();
    }

    // -------------------------------------------------------------------------
    // /graphql – scope enforcement
    // -------------------------------------------------------------------------

    public function test_key_missing_events_scope_gets_403(): void
    {
        // Give the key orders:read but NOT events:read.
        ['rawKey' => $rawKey] = $this->makeOrganizerWithKey(['scopes' => ['orders:read']]);

        $response = $this->withToken($rawKey)
            ->postJson('/graphql', ['query' => self::EVENTS_QUERY]);

        $response->assertForbidden();
    }

    public function test_key_missing_orders_scope_gets_403(): void
    {
        ['rawKey' => $rawKey] = $this->makeOrganizerWithKey(['scopes' => ['events:read']]);

        $response = $this->withToken($rawKey)
            ->postJson('/graphql', ['query' => '{ orders { id status } }']);

        $response->assertForbidden();
    }

    public function test_key_missing_tickets_scope_gets_403(): void
    {
        ['rawKey' => $rawKey] = $this->makeOrganizerWithKey(['scopes' => ['events:read']]);

        $response = $this->withToken($rawKey)
            ->postJson('/graphql', ['query' => '{ tickets { id status } }']);

        $response->assertForbidden();
    }

    public function test_key_with_all_scopes_can_query_events_orders_tickets(): void
    {
        ['rawKey' => $rawKey] = $this->makeOrganizerWithKey([
            'scopes' => ['events:read', 'orders:read', 'tickets:read'],
        ]);

        foreach (['events' => 'id title', 'orders' => 'id status', 'tickets' => 'id status'] as $resource => $fields) {
            $response = $this->withToken($rawKey)
                ->postJson('/graphql', ['query' => "{ {$resource} { {$fields} } }"]);

            $response->assertOk();
            $response->assertJsonStructure(['data' => [$resource]]);
        }
    }
}
