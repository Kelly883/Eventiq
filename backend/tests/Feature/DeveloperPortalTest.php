<?php

namespace Tests\Feature;

use App\Features\ApiKeys\Services\ApiKeyService;
use App\Models\ApiKey;
use App\Models\AuditLog;
use App\Models\Organizer;
use App\Models\Role;
use App\Models\User;
use App\Models\Webhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class DeveloperPortalTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrganizer(): array
    {
        $organizerRole = Role::factory()->create(['name' => 'organizer']);
        $user = User::factory()->create(['role_id' => $organizerRole->id]);
        $organizer = Organizer::factory()->for($user)->create();

        return compact('user', 'organizer');
    }

    private function makeRegularUser(): User
    {
        $role = Role::factory()->create(['name' => 'attendee']);
        return User::factory()->create(['role_id' => $role->id]);
    }

    private function createApiKeyFor(Organizer $organizer, string $name = 'My Key'): ApiKey
    {
        $raw = Str::random(32);
        return ApiKey::factory()->for($organizer)->create([
            'name' => $name,
            'key_prefix' => 'ek_' . Str::random(8),
            'hashed_key' => Hash::make($raw),
            'key_hash_index' => hash('sha256', $raw),
            'scopes' => ['events:read'],
        ]);
    }

    // ------------------------------------------------------------------
    // Auth / role guards
    // ------------------------------------------------------------------

    public function test_guest_cannot_access_developer_routes(): void
    {
        $this->getJson('/api/developer/api-keys')->assertStatus(401);
        $this->getJson('/api/developer/webhooks')->assertStatus(401);
        $this->getJson('/api/developer/api-logs')->assertStatus(401);
    }

    public function test_non_organizer_cannot_access_developer_routes(): void
    {
        $user = $this->makeRegularUser();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/developer/api-keys')
            ->assertStatus(403);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/developer/webhooks', [
                'url' => 'https://example.com/hook',
                'subscribedEvents' => ['order.created'],
            ])
            ->assertStatus(403);
    }

    // ------------------------------------------------------------------
    // API keys
    // ------------------------------------------------------------------

    public function test_organizer_can_list_their_api_keys(): void
    {
        ['user' => $user, 'organizer' => $organizer] = $this->makeOrganizer();
        $this->createApiKeyFor($organizer, 'Key A');
        $this->createApiKeyFor($organizer, 'Key B');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/developer/api-keys')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['name' => 'Key A'])
            ->assertJsonFragment(['name' => 'Key B']);
    }

    public function test_organizer_cannot_see_another_organizers_api_keys(): void
    {
        ['user' => $user, 'organizer' => $organizer] = $this->makeOrganizer();
        $other = Organizer::factory()->create();
        $otherUser = User::factory()->create(['role_id' => $user->role_id]);
        $other->update(['user_id' => $otherUser->id]);
        $this->createApiKeyFor($other, 'Other Org Key');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/developer/api-keys')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_organizer_can_create_api_key_and_receives_raw_key_once(): void
    {
        ['user' => $user, 'organizer' => $organizer] = $this->makeOrganizer();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/developer/api-keys', [
                'name' => 'Integration key',
                'scopes' => ['events:read', 'orders:read'],
            ])
            ->assertStatus(201);

        $response->assertJsonStructure(['api_key' => ['id', 'name'], 'raw_key']);
        $rawKey = $response->json('raw_key');
        $keyPrefix = substr($rawKey, 0, 8);

        $this->assertDatabaseHas('api_keys', [
            'organizer_id' => $organizer->id,
            'name' => 'Integration key',
            'key_prefix' => $keyPrefix,
        ]);
    }

    public function test_creating_key_with_reserved_write_scope_is_rejected(): void
    {
        ['user' => $user] = $this->makeOrganizer();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/developer/api-keys', [
                'name' => 'Write-scoped key',
                'scopes' => ['events:read', 'events:write'],
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', fn (string $message) => str_contains($message, 'cannot be granted'));

        $this->assertDatabaseMissing('api_keys', ['name' => 'Write-scoped key']);
    }

    public function test_organizer_can_revoke_their_api_key(): void
    {
        ['user' => $user, 'organizer' => $organizer] = $this->makeOrganizer();
        $key = $this->createApiKeyFor($organizer);

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/developer/api-keys/' . $key->id)
            ->assertOk();

        $this->assertNotNull($key->fresh()->revoked_at);
    }

    public function test_organizer_cannot_revoke_another_organizers_api_key(): void
    {
        ['user' => $user] = $this->makeOrganizer();
        $other = Organizer::factory()->create();
        $key = $this->createApiKeyFor($other);

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/developer/api-keys/' . $key->id)
            ->assertNotFound();

        $this->assertNull($key->fresh()->revoked_at);
    }

    // ------------------------------------------------------------------
    // Webhooks
    // ------------------------------------------------------------------

    public function test_organizer_can_list_and_create_webhooks(): void
    {
        ['user' => $user] = $this->makeOrganizer();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/developer/webhooks', [
                'url' => 'https://example.com/hooks/order-created',
                'description' => 'Order notifications',
                'subscribedEvents' => ['order.created', 'payment.succeeded'],
            ])
            ->assertStatus(201)
            ->assertJsonPath('subscribed_events', ['order.created', 'payment.succeeded']);

        $webhook = Webhook::first();
        $this->assertNotNull($webhook);
        $this->assertSame(['order.created', 'payment.succeeded'], $webhook->subscribed_events);
        $this->assertNotEmpty($webhook->secret);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'webhook_created',
            'source' => 'developer_portal',
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/developer/webhooks')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_webhook_requires_valid_event(): void
    {
        ['user' => $user] = $this->makeOrganizer();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/developer/webhooks', [
                'url' => 'https://example.com/hook',
                'subscribedEvents' => ['not.a.real.event'],
            ])
            ->assertUnprocessable();
    }

    public function test_organizer_can_delete_webhook(): void
    {
        ['user' => $user] = $this->makeOrganizer();
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
            ->assertOk();

        $this->assertDatabaseMissing('webhooks', ['id' => $webhook->id]);
    }

    // ------------------------------------------------------------------
    // API logs
    // ------------------------------------------------------------------

    public function test_organizer_can_list_their_api_logs(): void
    {
        ['user' => $user] = $this->makeOrganizer();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'webhook_created',
            'target_type' => Webhook::class,
            'target_id' => Str::uuid()->toString(),
            'status' => 'success',
            'source' => 'developer_portal',
            'ip_address' => '127.0.0.1',
            'metadata' => ['path' => '/api/developer/webhooks', 'url' => 'https://example.com/hook'],
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/developer/api-logs')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.action', 'webhook_created')
            ->assertJsonPath('data.0.path', '/api/developer/webhooks');
    }

    // ------------------------------------------------------------------
    // Public API routes (v1/events + graphql) behind API key auth
    // ------------------------------------------------------------------

    public function test_v1_events_requires_api_key(): void
    {
        $this->getJson('/api/v1/events')->assertStatus(401);
    }

    public function test_graphql_requires_api_key(): void
    {
        $this->postJson('/graphql', ['query' => '{ events { id } }'])->assertStatus(401);
    }
}
