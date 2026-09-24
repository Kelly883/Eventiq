<?php

namespace Tests\Feature;

use App\Features\PushNotifications\Models\PushNotificationDevice;
use App\Features\PushNotifications\Models\PushNotificationTemplate;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PushNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    private function makeUser(string $role = 'attendee'): User
    {
        $user = User::factory()->create(['emailVerified' => true]);
        $user->organizer()->firstOrCreate([], ['displayName' => $user->name ?? 'Test User']);

        if ($role === 'admin') {
            $adminRole = Role::firstOrCreate(['name' => 'admin'], ['description' => 'Administrator', 'isSystemRole' => true]);
            if (!$user->roles()->where('name', 'admin')->exists()) {
                $user->roles()->attach($adminRole);
            }
        }

        return $user;
    }

    // ------------------------------------------------------------------
    // Auth / Authorization
    // ------------------------------------------------------------------

    public function test_register_device_token_requires_authentication(): void
    {
        $this->postJson('/api/notifications/device-tokens', [])->assertUnauthorized();
    }

    public function test_delete_device_token_requires_authentication(): void
    {
        $this->deleteJson('/api/notifications/device-tokens', [])->assertUnauthorized();
    }

    public function test_list_push_templates_requires_authentication(): void
    {
        $this->getJson('/api/admin/push-templates')->assertUnauthorized();
    }

    public function test_list_push_templates_requires_admin_role(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user, 'sanctum')->getJson('/api/admin/push-templates')->assertForbidden();
    }

    public function test_create_push_template_requires_authentication(): void
    {
        $this->postJson('/api/admin/push-templates', [])->assertUnauthorized();
    }

    public function test_create_push_template_requires_admin_role(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user, 'sanctum')->postJson('/api/admin/push-templates', [])->assertForbidden();
    }

    public function test_update_push_template_requires_authentication(): void
    {
        $this->patchJson('/api/admin/push-templates/some-id', [])->assertUnauthorized();
    }

    public function test_delete_push_template_requires_authentication(): void
    {
        $this->deleteJson('/api/admin/push-templates/some-id')->assertUnauthorized();
    }

    public function test_send_test_push_requires_authentication(): void
    {
        $this->postJson('/api/admin/push-templates/send-test', [])->assertUnauthorized();
    }

    // ------------------------------------------------------------------
    // POST /api/notifications/device-tokens
    // ------------------------------------------------------------------

    public function test_register_device_token_creates_record(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/notifications/device-tokens', [
            'token' => 'test-token-123',
            'provider' => 'fcm',
            'device_type' => 'android',
            'device_name' => 'Pixel 6',
            'model' => 'Google Pixel 6',
            'app_version' => '1.0.0',
            'os_version' => '14.0',
            'locale' => 'en',
            'timezone' => 'UTC',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('push_notification_devices', [
            'token' => 'test-token-123',
            'user_id' => $user->id,
            'provider' => 'fcm',
            'device_type' => 'android',
        ]);
    }

    public function test_register_same_token_updates_existing_record(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')->postJson('/api/notifications/device-tokens', [
            'token' => 'duplicate-token',
            'provider' => 'fcm',
            'device_type' => 'android',
        ])->assertStatus(201);

        $this->actingAs($user, 'sanctum')->postJson('/api/notifications/device-tokens', [
            'token' => 'duplicate-token',
            'provider' => 'fcm',
            'device_type' => 'ios',
        ])->assertStatus(201);

        $this->assertDatabaseCount('push_notification_devices', 1);
        $this->assertDatabaseHas('push_notification_devices', [
            'token' => 'duplicate-token',
            'device_type' => 'ios',
        ]);
    }

    public function test_register_device_token_requires_valid_provider(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/notifications/device-tokens', [
            'token' => 'test-token',
            'provider' => 'invalid-provider',
            'device_type' => 'android',
        ]);

        $response->assertStatus(422);
    }

    public function test_register_device_token_requires_valid_device_type(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/notifications/device-tokens', [
            'token' => 'test-token',
            'provider' => 'fcm',
            'device_type' => 'windows-phone',
        ]);

        $response->assertStatus(422);
    }

    // ------------------------------------------------------------------
    // DELETE /api/notifications/device-tokens
    // ------------------------------------------------------------------

    public function test_delete_device_token_soft_deletes(): void
    {
        $user = $this->makeUser();
        $device = PushNotificationDevice::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')->deleteJson('/api/notifications/device-tokens', [
            'token' => $device->token,
        ]);

        $response->assertStatus(204);
        $this->assertSoftDeleted('push_notification_devices', ['id' => $device->id]);
    }

    public function test_delete_other_users_token_returns_404(): void
    {
        $user = $this->makeUser();
        $otherUser = $this->makeUser();
        $device = PushNotificationDevice::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user, 'sanctum')->deleteJson('/api/notifications/device-tokens', [
            'token' => $device->token,
        ]);

        $response->assertNotFound();
    }

    public function test_delete_nonexistent_token_returns_404(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user, 'sanctum')->deleteJson('/api/notifications/device-tokens', [
            'token' => 'nonexistent-token',
        ]);

        $response->assertNotFound();
    }

    // ------------------------------------------------------------------
    // GET /api/admin/push-templates
    // ------------------------------------------------------------------

    public function test_list_push_templates(): void
    {
        $admin = $this->makeUser('admin');
        PushNotificationTemplate::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/admin/push-templates');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    // ------------------------------------------------------------------
    // POST /api/admin/push-templates
    // ------------------------------------------------------------------

    public function test_create_push_template(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/admin/push-templates', [
            'name' => 'Order Confirmation',
            'type' => 'order_confirmation',
            'title' => 'Your order is confirmed',
            'body' => 'Thank you for your purchase!',
            'variables' => ['name', 'order_id'],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Order Confirmation');

        $this->assertDatabaseHas('push_notification_templates', [
            'name' => 'Order Confirmation',
            'type' => 'order_confirmation',
        ]);
    }

    public function test_create_push_template_validates_title_max(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/admin/push-templates', [
            'name' => 'Test',
            'type' => 'order_confirmation',
            'title' => str_repeat('a', 66),
            'body' => 'Body',
        ]);

        $response->assertStatus(422);
    }

    public function test_create_push_template_validates_body_max(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/admin/push-templates', [
            'name' => 'Test',
            'type' => 'order_confirmation',
            'title' => 'Title',
            'body' => str_repeat('a', 179),
        ]);

        $response->assertStatus(422);
    }

    // ------------------------------------------------------------------
    // PATCH /api/admin/push-templates/:templateId
    // ------------------------------------------------------------------

    public function test_update_push_template(): void
    {
        $admin = $this->makeUser('admin');
        $template = PushNotificationTemplate::factory()->create(['title' => 'Old Title']);

        $response = $this->actingAs($admin, 'sanctum')->patchJson('/api/admin/push-templates/' . $template->id, [
            'title' => 'New Title',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.title', 'New Title');

        $this->assertEquals('New Title', $template->fresh()->title);
    }

    // ------------------------------------------------------------------
    // DELETE /api/admin/push-templates/:templateId
    // ------------------------------------------------------------------

    public function test_delete_push_template(): void
    {
        $admin = $this->makeUser('admin');
        $template = PushNotificationTemplate::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')->deleteJson('/api/admin/push-templates/' . $template->id);

        $response->assertStatus(204);
        $this->assertSoftDeleted('push_notification_templates', ['id' => $template->id]);
    }

    // ------------------------------------------------------------------
    // POST /api/admin/push-templates/send-test
    // ------------------------------------------------------------------

    public function test_send_test_queues_jobs(): void
    {
        $admin = $this->makeUser('admin');
        $user = $this->makeUser();
        PushNotificationDevice::factory()->count(2)->create(['user_id' => $user->id]);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/admin/push-templates/send-test', [
            'user_id' => $user->id,
            'title' => 'Test',
            'body' => 'Test body',
        ]);

        $response->assertStatus(202)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Test notifications queued for 2 device(s).');

        Queue::assertPushed(\App\Features\PushNotifications\Jobs\SendPushNotificationJob::class, 2);
    }

    public function test_send_test_with_no_devices(): void
    {
        $admin = $this->makeUser('admin');
        $user = $this->makeUser();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/admin/push-templates/send-test', [
            'user_id' => $user->id,
            'title' => 'Test',
            'body' => 'Test body',
        ]);

        $response->assertStatus(202)
            ->assertJsonPath('message', 'Test notifications queued for 0 device(s).');
    }

    // ------------------------------------------------------------------
    // Rate limiting
    // ------------------------------------------------------------------

    public function test_register_device_token_rate_limited(): void
    {
        $user = $this->makeUser();

        for ($i = 0; $i < 20; $i++) {
            $this->actingAs($user, 'sanctum')
                ->postJson('/api/notifications/device-tokens', [
                    'token' => "token-{$i}",
                    'provider' => 'fcm',
                    'device_type' => 'android',
                ])
                ->assertStatus(201);
        }

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/notifications/device-tokens', [
                'token' => 'over-limit',
                'provider' => 'fcm',
                'device_type' => 'android',
            ])
            ->assertStatus(429);
    }
}
