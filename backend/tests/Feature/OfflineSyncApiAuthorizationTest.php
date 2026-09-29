<?php

namespace Tests\Feature;

use App\Features\PushNotifications\Models\PushNotificationDevice;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfflineSyncApiAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function makeUserWithRole(string $roleName, array $extra = []): User
    {
        $role = Role::firstOrCreate(['name' => $roleName], ['description' => ucfirst($roleName)]);
        $user = User::factory()->create($extra);
        $user->roles()->attach($role);
        return $user;
    }

    private function makeDevice(User $user, array $overrides = []): PushNotificationDevice
    {
        $device = PushNotificationDevice::create(array_merge([
            'user_id' => $user->id,
            'token' => strtolower(bin2hex(random_bytes(32))),
            'provider' => 'web',
            'device_type' => 'web',
            'offline_enabled' => true,
        ], $overrides));

        $device->raw_device_secret = $device->generateDeviceSecret();

        return $device;
    }

    public function test_authenticated_user_can_update_offline_status(): void
    {
        $user = $this->makeUserWithRole('organizer');
        $device = $this->makeDevice($user);

        $response = $this->actingAs($user, 'sanctum')
            ->patchJson("/api/notifications/device-tokens/{$device->getDecryptedToken()}/offline-status", [
                'offline_enabled' => false,
            ]);

        $response->assertOk()
            ->assertJsonStructure(['offline_enabled']);

        $this->assertFalse($device->fresh()->offline_enabled);
    }

    public function test_unauthenticated_user_cannot_update_offline_status(): void
    {
        $response = $this->patchJson('/api/notifications/device-tokens/'.strtolower(bin2hex(random_bytes(32))).'/offline-status', [
            'offline_enabled' => false,
        ]);

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_fetch_offline_sync_tickets(): void
    {
        $user = $this->makeUserWithRole('organizer');
        $device = $this->makeDevice($user);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/me/tickets/for-offline-sync', [
                'X-Device-Token' => $device->token,
                'X-Device-Secret' => $device->raw_device_secret,
            ]);

        $response->assertOk()
            ->assertJsonStructure(['data', 'pagination', 'meta']);

        $this->assertTrue($device->fresh()->last_sync_at !== null);
        $this->assertNotNull($response->json('meta.last_sync_at'));
    }

    public function test_unauthenticated_user_cannot_fetch_offline_sync_tickets(): void
    {
        $response = $this->getJson('/api/me/tickets/for-offline-sync');

        $response->assertUnauthorized();
    }

    public function test_offline_sync_requires_device_token_header(): void
    {
        $user = $this->makeUserWithRole('organizer');

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/me/tickets/for-offline-sync');

        $response->assertStatus(400)
            ->assertJson(['message' => 'X-Device-Token header is required.']);
    }

    public function test_offline_sync_requires_device_secret_header(): void
    {
        $user = $this->makeUserWithRole('organizer');
        $device = $this->makeDevice($user);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/me/tickets/for-offline-sync', [
                'X-Device-Token' => $device->token,
            ]);

        $response->assertStatus(400)
            ->assertJson(['message' => 'X-Device-Secret header is required.']);
    }

    public function test_offline_sync_returns_404_for_invalid_device_secret(): void
    {
        $user = $this->makeUserWithRole('organizer');
        $device = $this->makeDevice($user);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/me/tickets/for-offline-sync', [
                'X-Device-Token' => $device->token,
                'X-Device-Secret' => 'invalid-secret',
            ]);

        $response->assertStatus(404)
            ->assertJson(['message' => 'Device not found or invalid secret']);
    }

    public function test_offline_sync_returns_404_for_device_not_belonging_to_user(): void
    {
        $user = $this->makeUserWithRole('organizer');
        $otherUser = $this->makeUserWithRole('organizer');
        $otherDevice = $this->makeDevice($otherUser);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/me/tickets/for-offline-sync', [
                'X-Device-Token' => $otherDevice->token,
                'X-Device-Secret' => $otherDevice->raw_device_secret,
            ]);

        $response->assertStatus(404);
    }

    public function test_offline_sync_returns_conflict_ids_when_tickets_modified(): void
    {
        $user = $this->makeUserWithRole('organizer');
        $organizer = \App\Models\Organizer::factory()->create(['user_id' => $user->id]);
        $device = $this->makeDevice($user);
        $event = \App\Models\Event::factory()->create(['organizer_id' => $organizer->id, 'status' => 'published', 'start_datetime' => now()->addDays(7)]);
        $ticket = \App\Features\Checkout\Models\Ticket::factory()->create(['event_id' => $event->id]);

        $lastSyncAt = now()->subHour()->toIso8601String();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/me/tickets/for-offline-sync?last_sync_at=' . $lastSyncAt, [
                'X-Device-Token' => $device->token,
                'X-Device-Secret' => $device->raw_device_secret,
            ]);

        $response->assertOk();
        $this->assertIsArray($response->json('meta.conflict_ticket_ids'));
    }

    public function test_logout_deletes_user_device_tokens(): void
    {
        $user = $this->makeUserWithRole('organizer');
        $device = $this->makeDevice($user);

        $this->assertDatabaseHas('push_notification_devices', ['id' => $device->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/auth/logout');

        $response->assertOk();
        $this->assertSoftDeleted('push_notification_devices', ['id' => $device->id]);
    }
}
