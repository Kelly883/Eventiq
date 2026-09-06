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
        $role = Role::create(['name' => $roleName, 'description' => ucfirst($roleName)]);
        $user = User::factory()->create($extra);
        $user->roles()->attach($role);
        return $user;
    }

    private function makeDevice(User $user, array $overrides = []): PushNotificationDevice
    {
        return PushNotificationDevice::create(array_merge([
            'user_id' => $user->id,
            'token' => strtolower(bin2hex(random_bytes(32))),
            'provider' => 'web',
            'device_type' => 'web',
            'offline_enabled' => true,
        ], $overrides));
    }

    public function test_authenticated_user_can_update_offline_status(): void
    {
        $user = $this->makeUserWithRole('organizer');
        $device = $this->makeDevice($user);

        $response = $this->actingAs($user, 'sanctum')
            ->patchJson("/api/notifications/device-tokens/{$device->token}/offline-status", [
                'offline_enabled' => false,
            ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'offline_enabled']);

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
            ]);

        $response->assertOk()
            ->assertJsonStructure(['data', 'pagination']);

        $this->assertTrue($device->fresh()->last_used_at !== null);
    }

    public function test_unauthenticated_user_cannot_fetch_offline_sync_tickets(): void
    {
        $response = $this->getJson('/api/me/tickets/for-offline-sync');

        $response->assertUnauthorized();
    }

    public function test_offline_sync_works_without_device_token_header(): void
    {
        $user = $this->makeUserWithRole('organizer');

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/me/tickets/for-offline-sync');

        $response->assertOk()
            ->assertJsonStructure(['data', 'pagination']);
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
