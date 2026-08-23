<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\PermissionRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_submit_request(): void
    {
        $this->postJson('/api/permissions/request', [
            'requestedPermission' => 'admin-access',
            'reason' => 'I need admin access for work.',
        ])->assertStatus(401);
    }

    public function test_validation_rejects_missing_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/permissions/request', []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['requestedPermission', 'reason']);
    }

    public function test_validation_rejects_unknown_access_level(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/permissions/request', [
            'requestedPermission' => 'superuser-everything',
            'reason' => 'Because I want all the power.',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['requestedPermission']);
    }

    public function test_user_can_submit_request(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/permissions/request', [
            'requestedPermission' => 'admin-access',
            'reason' => 'Our team admin left and I need to manage roles.',
        ]);

        $response->assertStatus(201);
        $response->assertJsonFragment(['message' => 'Your access request has been submitted for review.']);

        $this->assertDatabaseHas('permission_requests', [
            'userId' => $user->id,
            'status' => PermissionRequest::STATUS_PENDING,
        ]);
        $this->assertDatabaseHas('permissions', ['name' => 'admin-access']);
    }

    public function test_duplicate_pending_request_is_rejected(): void
    {
        $user = User::factory()->create();
        $permission = Permission::create(['name' => 'admin-access', 'group' => 'access']);

        PermissionRequest::create([
            'userId' => $user->id,
            'permissionId' => $permission->id,
            'reason' => 'Original pending request.',
            'status' => PermissionRequest::STATUS_PENDING,
        ]);

        $response = $this->actingAs($user)->postJson('/api/permissions/request', [
            'requestedPermission' => 'admin-access',
            'reason' => 'Second attempt while first is still pending.',
        ]);

        $response->assertStatus(409);
        $this->assertSame(
            1,
            PermissionRequest::where('userId', $user->id)->count()
        );
    }

    public function test_user_can_resubmit_after_denial(): void
    {
        $user = User::factory()->create();
        $permission = Permission::create(['name' => 'admin-access', 'group' => 'access']);

        PermissionRequest::create([
            'userId' => $user->id,
            'permissionId' => $permission->id,
            'reason' => 'Denied earlier.',
            'status' => PermissionRequest::STATUS_DENIED,
        ]);

        $response = $this->actingAs($user)->postJson('/api/permissions/request', [
            'requestedPermission' => 'admin-access',
            'reason' => 'Circumstances have changed, requesting again.',
        ]);

        $response->assertStatus(201);
        $this->assertSame(
            2,
            PermissionRequest::where('userId', $user->id)->count()
        );
    }
}
