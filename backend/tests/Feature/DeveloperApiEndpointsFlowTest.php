<?php

namespace Tests\Feature;

use App\Features\ApiKeys\Services\ApiKeyService;
use App\Models\ApiKey;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Role;
use App\Models\User;
use App\Models\Webhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class DeveloperApiEndpointsFlowTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrganizer(): array
    {
        $organizerRole = Role::firstOrCreate(['name' => 'organizer']);
        $user = User::factory()->create(['role_id' => $organizerRole->id]);
        $organizer = Organizer::factory()->for($user)->create(['isPublic' => true, 'verificationStatus' => 'verified']);

        return compact('user', 'organizer');
    }

    private function makeRegularUser(): User
    {
        $role = Role::firstOrCreate(['name' => 'attendee']);
        return User::factory()->create(['role_id' => $role->id]);
    }

    // ------------------------------------------------------------------
    // 1. GET /api/developer/api-keys
    // ------------------------------------------------------------------

    public function test_api_keys_returns_only_authenticated_users_keys(): void
    {
        ['user' => $user, 'organizer' => $organizer] = $this->makeOrganizer();
        $other = Organizer::factory()->create();
        $otherUser = User::factory()->create(['role_id' => $user->role_id]);
        $other->update(['user_id' => $otherUser->id]);

        ApiKey::factory()->for($organizer)->create(['name' => 'My Key']);
        ApiKey::factory()->for($other)->create(['name' => 'Other Key']);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/developer/api-keys')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['name' => 'My Key'])
            ->assertJsonMissing(['name' => 'Other Key']);
    }

    // ------------------------------------------------------------------
    // 2. POST /api/developer/api-keys
    // ------------------------------------------------------------------

    public function test_api_key_creation_returns_raw_key_once(): void
    {
        ['user' => $user, 'organizer' => $organizer] = $this->makeOrganizer();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/developer/api-keys', [
                'name' => 'Integration Key',
                'scopes' => ['events:read'],
            ])
            ->assertStatus(201)
            ->assertJsonStructure([
                'api_key' => ['id', 'name', 'key_prefix'],
                'raw_key',
                'warning',
            ]);

        $rawKey = $response->json('raw_key');
        $this->assertNotEmpty($rawKey);
        $this->assertSame(49, strlen($rawKey));

        $prefix = substr($rawKey, 0, 8);
        $this->assertDatabaseHas('api_keys', [
            'organizer_id' => $organizer->id,
            'name' => 'Integration Key',
            'key_prefix' => $prefix,
        ]);

        $this->assertDatabaseMissing('api_keys', [
            'hashed_key' => $rawKey,
        ]);
    }

    // ------------------------------------------------------------------
    // 3. DELETE /api/developer/api-keys/:keyId
    // ------------------------------------------------------------------

    public function test_api_key_deletion_sets_revoked_at(): void
    {
        ['user' => $user, 'organizer' => $organizer] = $this->makeOrganizer();
        $key = ApiKey::factory()->for($organizer)->create();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/developer/api-keys/' . $key->id)
            ->assertNoContent();

        $this->assertNotNull($key->fresh()->revoked_at);
        $this->assertTrue($key->fresh()->isRevoked());
    }

    // ------------------------------------------------------------------
    // 4. GET /api/developer/webhooks
    // ------------------------------------------------------------------

    public function test_webhooks_returns_only_authenticated_users_webhooks(): void
    {
        ['user' => $user, 'organizer' => $organizer] = $this->makeOrganizer();
        $other = Organizer::factory()->create();
        $otherUser = User::factory()->create(['role_id' => $user->role_id]);
        $other->update(['user_id' => $otherUser->id]);

        Webhook::create([
            'organizer_id' => $user->id,
            'url' => 'https://example.com/my-hook',
            'secret' => Webhook::generateSecret(),
            'subscribed_events' => ['order.created'],
            'status' => 'active',
            'failure_count' => 0,
            'timeout_seconds' => 10,
        ]);
        Webhook::create([
            'organizer_id' => $otherUser->id,
            'url' => 'https://example.com/other-hook',
            'secret' => Webhook::generateSecret(),
            'subscribed_events' => ['order.created'],
            'status' => 'active',
            'failure_count' => 0,
            'timeout_seconds' => 10,
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/developer/webhooks')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['url' => 'https://example.com/my-hook'])
            ->assertJsonMissing(['url' => 'https://example.com/other-hook']);
    }

    // ------------------------------------------------------------------
    // 5. POST /api/developer/webhooks
    // ------------------------------------------------------------------

    public function test_webhook_creation_generates_secret(): void
    {
        ['user' => $user, 'organizer' => $organizer] = $this->makeOrganizer();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/developer/webhooks', [
                'url' => 'https://example.com/hooks/order-created',
                'description' => 'Order notifications',
                'subscribedEvents' => ['order.created', 'payment.succeeded'],
            ])
            ->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'url',
                'secret',
                'subscribed_events',
                'status',
            ]);

        $this->assertNotEmpty($response->json('secret'));
        $this->assertSame('active', $response->json('status'));

        $webhook = Webhook::first();
        $this->assertNotNull($webhook->secret);
        $this->assertNotEmpty($webhook->secret);
    }

    // ------------------------------------------------------------------
    // 6. DELETE /api/developer/webhooks/:webhookId
    // ------------------------------------------------------------------

    public function test_webhook_deletion_removes_it(): void
    {
        ['user' => $user, 'organizer' => $organizer] = $this->makeOrganizer();
        $webhook = Webhook::create([
            'organizer_id' => $user->id,
            'url' => 'https://example.com/hook',
            'secret' => Webhook::generateSecret(),
            'subscribed_events' => ['order.created'],
            'status' => 'active',
            'failure_count' => 0,
            'timeout_seconds' => 10,
        ]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/developer/webhooks/' . $webhook->id)
            ->assertNoContent();

        $this->assertDatabaseMissing('webhooks', ['id' => $webhook->id]);
    }

    // ------------------------------------------------------------------
    // 7. GET /api/developer/api-logs
    // ------------------------------------------------------------------

    public function test_api_logs_returns_user_logs(): void
    {
        ['user' => $user, 'organizer' => $organizer] = $this->makeOrganizer();
        $other = Organizer::factory()->create();
        $otherUser = User::factory()->create(['role_id' => $user->role_id]);
        $other->update(['user_id' => $otherUser->id]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'api_call',
            'target_type' => Webhook::class,
            'target_id' => Str::uuid()->toString(),
            'status' => 'success',
            'source' => 'developer_portal',
            'ip_address' => '127.0.0.1',
            'metadata' => ['path' => '/api/developer/webhooks', 'url' => 'https://example.com/hook'],
        ]);

        AuditLog::create([
            'user_id' => $otherUser->id,
            'action' => 'api_call',
            'target_type' => Webhook::class,
            'target_id' => Str::uuid()->toString(),
            'status' => 'success',
            'source' => 'developer_portal',
            'ip_address' => '127.0.0.1',
            'metadata' => ['path' => '/api/developer/webhooks'],
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/developer/api-logs')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.action', 'api_call')
            ->assertJsonPath('data.0.path', '/api/developer/webhooks');
    }

    // ------------------------------------------------------------------
    // 8. GET /v1/events with API key
    // ------------------------------------------------------------------

    public function test_v1_events_with_api_key_and_scope(): void
    {
        ['user' => $user, 'organizer' => $organizer] = $this->makeOrganizer();
        Event::factory()->for($organizer)->count(3)->create(['status' => 'published', 'is_public' => true]);

        $prefix = 'ek_v1_' . Str::random(8);
        $secret = Str::random(32);
        $rawKey = "{$prefix}|{$secret}";

        ApiKey::factory()->for($organizer)->create([
            'key_prefix' => $prefix,
            'hashed_key' => Hash::make($rawKey),
            'key_hash_index' => hash('sha256', $rawKey),
            'scopes' => ['events:read'],
        ]);

        $this->withToken($rawKey)
            ->getJson('/api/v1/events')
            ->assertOk()
            ->assertJsonStructure(['data' => []]);
    }

    public function test_v1_events_without_api_key_returns_401(): void
    {
        $this->getJson('/api/v1/events')->assertStatus(401);
    }

    public function test_v1_events_with_revoked_key_returns_401(): void
    {
        ['user' => $user, 'organizer' => $organizer] = $this->makeOrganizer();
        $prefix = 'ek_rev_' . Str::random(8);
        $secret = Str::random(32);
        $rawKey = "{$prefix}|{$secret}";

        $key = ApiKey::factory()->for($organizer)->create([
            'key_prefix' => $prefix,
            'hashed_key' => Hash::make($rawKey),
            'key_hash_index' => hash('sha256', $rawKey),
            'scopes' => ['events:read'],
            'revoked_at' => now(),
        ]);

        $this->withToken($rawKey)
            ->getJson('/api/v1/events')
            ->assertStatus(401);
    }

    public function test_v1_events_with_expired_key_returns_401(): void
    {
        ['user' => $user, 'organizer' => $organizer] = $this->makeOrganizer();
        $prefix = 'ek_exp_' . Str::random(8);
        $secret = Str::random(32);
        $rawKey = "{$prefix}|{$secret}";

        $key = ApiKey::factory()->for($organizer)->create([
            'key_prefix' => $prefix,
            'hashed_key' => Hash::make($rawKey),
            'key_hash_index' => hash('sha256', $rawKey),
            'scopes' => ['events:read'],
            'expires_at' => now()->subDay(),
        ]);

        $this->withToken($rawKey)
            ->getJson('/api/v1/events')
            ->assertStatus(401);
    }

    // ------------------------------------------------------------------
    // 9. POST /graphql with API key
    // ------------------------------------------------------------------

    public function test_graphql_with_api_key_and_scope(): void
    {
        ['user' => $user, 'organizer' => $organizer] = $this->makeOrganizer();
        Event::factory()->for($organizer)->count(2)->create(['status' => 'published', 'is_public' => true]);

        $prefix = 'ek_gql_' . Str::random(8);
        $secret = Str::random(32);
        $rawKey = "{$prefix}|{$secret}";

        ApiKey::factory()->for($organizer)->create([
            'key_prefix' => $prefix,
            'hashed_key' => Hash::make($rawKey),
            'key_hash_index' => hash('sha256', $rawKey),
            'scopes' => ['events:read'],
        ]);

        $this->withToken($rawKey)
            ->postJson('/graphql', ['query' => '{ events { id title } }'])
            ->assertOk()
            ->assertJsonStructure(['data' => ['events']]);
    }

    public function test_graphql_without_auth_returns_401(): void
    {
        $this->postJson('/graphql', ['query' => '{ events { id } }'])->assertStatus(401);
    }

    public function test_graphql_with_revoked_key_returns_401(): void
    {
        ['user' => $user, 'organizer' => $organizer] = $this->makeOrganizer();
        $prefix = 'ek_gql_rev_' . Str::random(8);
        $secret = Str::random(32);
        $rawKey = "{$prefix}|{$secret}";

        $key = ApiKey::factory()->for($organizer)->create([
            'key_prefix' => $prefix,
            'hashed_key' => Hash::make($rawKey),
            'key_hash_index' => hash('sha256', $rawKey),
            'scopes' => ['events:read'],
            'revoked_at' => now(),
        ]);

        $this->withToken($rawKey)
            ->postJson('/graphql', ['query' => '{ events { id } }'])
            ->assertStatus(401);
    }

    // ------------------------------------------------------------------
    // Role guards
    // ------------------------------------------------------------------

    public function test_non_organizer_gets_403_on_all_developer_endpoints(): void
    {
        $user = $this->makeRegularUser();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/developer/api-keys')
            ->assertStatus(403);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/developer/webhooks')
            ->assertStatus(403);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/developer/api-logs')
            ->assertStatus(403);
    }

    public function test_guest_gets_401_on_all_developer_endpoints(): void
    {
        $this->getJson('/api/developer/api-keys')->assertStatus(401);
        $this->getJson('/api/developer/webhooks')->assertStatus(401);
        $this->getJson('/api/developer/api-logs')->assertStatus(401);
    }

    // ------------------------------------------------------------------
    // Status codes
    // ------------------------------------------------------------------

    public function test_creation_endpoints_return_201(): void
    {
        ['user' => $user, 'organizer' => $organizer] = $this->makeOrganizer();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/developer/api-keys', [
                'name' => 'Key',
                'scopes' => ['events:read'],
            ])
            ->assertStatus(201);

        $webhook = Webhook::create([
            'organizer_id' => $user->id,
            'url' => 'https://example.com/hook',
            'secret' => Webhook::generateSecret(),
            'subscribed_events' => ['order.created'],
            'status' => 'active',
            'failure_count' => 0,
            'timeout_seconds' => 10,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/developer/webhooks', [
                'url' => 'https://example.com/hook',
                'subscribedEvents' => ['order.created'],
            ])
            ->assertStatus(201);
    }

    public function test_deletion_endpoints_return_204(): void
    {
        ['user' => $user, 'organizer' => $organizer] = $this->makeOrganizer();
        $key = ApiKey::factory()->for($organizer)->create();
        $webhook = Webhook::create([
            'organizer_id' => $user->id,
            'url' => 'https://example.com/hook',
            'secret' => Webhook::generateSecret(),
            'subscribed_events' => ['order.created'],
            'status' => 'active',
            'failure_count' => 0,
            'timeout_seconds' => 10,
        ]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/developer/api-keys/' . $key->id)
            ->assertNoContent();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/developer/webhooks/' . $webhook->id)
            ->assertNoContent();
    }

    // ------------------------------------------------------------------
    // Audit logs verification
    // ------------------------------------------------------------------

    public function test_audit_logs_created_for_key_and_webhook_actions(): void
    {
        ['user' => $user, 'organizer' => $organizer] = $this->makeOrganizer();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/developer/api-keys', [
                'name' => 'Audit Key',
                'scopes' => ['events:read'],
            ])->assertStatus(201);

        $key = ApiKey::where('name', 'Audit Key')->first();
        $this->assertNotNull($key);

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/developer/api-keys/' . $key->id)
            ->assertNoContent();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/developer/webhooks', [
                'url' => 'https://example.com/hook',
                'subscribedEvents' => ['order.created'],
            ])->assertStatus(201);

        $webhook = Webhook::first();
        $this->assertNotNull($webhook);

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/developer/webhooks/' . $webhook->id)
            ->assertNoContent();

        $this->assertDatabaseHas('audit_logs', ['action' => 'api_key_created', 'user_id' => $user->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'api_key_revoked', 'user_id' => $user->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'webhook_created', 'user_id' => $user->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'webhook_deleted', 'user_id' => $user->id]);
    }

    // ------------------------------------------------------------------
    // Cross-organizer isolation
    // ------------------------------------------------------------------

    public function test_organizer_cannot_access_another_organizers_webhooks(): void
    {
        ['user' => $user, 'organizer' => $organizer] = $this->makeOrganizer();
        $other = Organizer::factory()->create();
        $otherUser = User::factory()->create(['role_id' => $user->role_id]);
        $other->update(['user_id' => $otherUser->id]);

        $webhook = Webhook::create([
            'organizer_id' => $otherUser->id,
            'url' => 'https://example.com/hook',
            'secret' => Webhook::generateSecret(),
            'subscribed_events' => ['order.created'],
            'status' => 'active',
            'failure_count' => 0,
            'timeout_seconds' => 10,
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/developer/webhooks')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/developer/webhooks/' . $webhook->id)
            ->assertNotFound();
    }

    public function test_organizer_cannot_access_another_organizers_api_logs(): void
    {
        ['user' => $user, 'organizer' => $organizer] = $this->makeOrganizer();
        $other = Organizer::factory()->create();
        $otherUser = User::factory()->create(['role_id' => $user->role_id]);
        $other->update(['user_id' => $otherUser->id]);

        AuditLog::create([
            'user_id' => $otherUser->id,
            'action' => 'api_call',
            'target_type' => Webhook::class,
            'target_id' => Str::uuid()->toString(),
            'status' => 'success',
            'source' => 'developer_portal',
            'ip_address' => '127.0.0.1',
            'metadata' => ['path' => '/api/developer/webhooks'],
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/developer/api-logs')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    // ------------------------------------------------------------------
    // API key usage tracking
    // ------------------------------------------------------------------

    public function test_api_key_usage_is_tracked(): void
    {
        ['user' => $user, 'organizer' => $organizer] = $this->makeOrganizer();
        $prefix = 'ek_use_' . Str::random(8);
        $secret = Str::random(32);
        $rawKey = "{$prefix}|{$secret}";

        $key = ApiKey::factory()->for($organizer)->create([
            'key_prefix' => $prefix,
            'hashed_key' => Hash::make($rawKey),
            'key_hash_index' => hash('sha256', $rawKey),
            'scopes' => ['events:read'],
        ]);

        $this->assertNull($key->last_used_at);

        $this->withToken($rawKey)
            ->getJson('/api/v1/events')
            ->assertOk();

        $this->assertNotNull($key->fresh()->last_used_at);
    }

    // ------------------------------------------------------------------
    // SSRF protection
    // ------------------------------------------------------------------

    public function test_webhook_rejects_private_ip_urls(): void
    {
        ['user' => $user] = $this->makeOrganizer();

        foreach (['http://10.0.0.1/hook', 'http://192.168.1.1/hook', 'http://127.0.0.1/hook'] as $privateUrl) {
            $this->actingAs($user, 'sanctum')
                ->postJson('/api/developer/webhooks', [
                    'url' => $privateUrl,
                    'subscribedEvents' => ['order.created'],
                ])
                ->assertUnprocessable()
                ->assertJsonPath('message', fn (string $message) => str_contains($message, 'private or internal address'));
        }
    }

    // ------------------------------------------------------------------
    // Pagination meta
    // ------------------------------------------------------------------

    public function test_webhook_index_returns_pagination_meta(): void
    {
        ['user' => $user, 'organizer' => $organizer] = $this->makeOrganizer();
        Webhook::create([
            'organizer_id' => $user->id,
            'url' => 'https://example.com/hook1',
            'secret' => Webhook::generateSecret(),
            'subscribed_events' => ['order.created'],
            'status' => 'active',
            'failure_count' => 0,
            'timeout_seconds' => 10,
        ]);
        Webhook::create([
            'organizer_id' => $user->id,
            'url' => 'https://example.com/hook2',
            'secret' => Webhook::generateSecret(),
            'subscribed_events' => ['order.created'],
            'status' => 'active',
            'failure_count' => 0,
            'timeout_seconds' => 10,
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/developer/webhooks')
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                'links' => ['first', 'last', 'prev', 'next'],
            ])
            ->assertJsonPath('meta.total', 2);
    }

    public function test_api_key_index_returns_pagination_meta(): void
    {
        ['user' => $user, 'organizer' => $organizer] = $this->makeOrganizer();
        ApiKey::factory()->for($organizer)->create(['name' => 'Key A']);
        ApiKey::factory()->for($organizer)->create(['name' => 'Key B']);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/developer/api-keys')
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                'links' => ['first', 'last', 'prev', 'next'],
            ])
            ->assertJsonPath('meta.total', 2);
    }

    // ------------------------------------------------------------------
    // Default rate limit on key creation
    // ------------------------------------------------------------------

    public function test_created_api_key_has_default_rate_limit(): void
    {
        ['user' => $user, 'organizer' => $organizer] = $this->makeOrganizer();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/developer/api-keys', [
                'name' => 'Rate Limited Key',
                'scopes' => ['events:read'],
            ])
            ->assertStatus(201);

        $key = ApiKey::where('name', 'Rate Limited Key')->first();
        $this->assertNotNull($key);
        $this->assertSame(100, $key->rate_limit);
        $this->assertSame('minute', $key->rate_limit_period);
    }

    // ------------------------------------------------------------------
    // Public API throttle
    // ------------------------------------------------------------------

    public function test_public_api_throttles_after_many_requests(): void
    {
        ['user' => $user, 'organizer' => $organizer] = $this->makeOrganizer();
        Event::factory()->for($organizer)->count(3)->create(['status' => 'published', 'is_public' => true]);

        $prefix = 'ek_thr_' . Str::random(8);
        $secret = Str::random(32);
        $rawKey = "{$prefix}|{$secret}";

        ApiKey::factory()->for($organizer)->create([
            'key_prefix' => $prefix,
            'hashed_key' => Hash::make($rawKey),
            'key_hash_index' => hash('sha256', $rawKey),
            'scopes' => ['events:read'],
            'rate_limit' => 5,
            'rate_limit_period' => 'minute',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->withToken($rawKey)
                ->getJson('/api/v1/events')
                ->assertOk();
        }

        $this->withToken($rawKey)
            ->getJson('/api/v1/events')
            ->assertStatus(429);
    }
}
