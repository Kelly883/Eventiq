<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        $adminRole = Role::create(['name' => 'admin', 'description' => 'Administrator', 'isSystemRole' => true]);
        $user = User::factory()->create();
        $user->roles()->attach($adminRole);
        return $user;
    }

    private function makeRegularUser(): User
    {
        $role = Role::create(['name' => 'organizer', 'description' => 'Event Organizer']);
        $user = User::factory()->create();
        $user->roles()->attach($role);
        return $user;
    }

    public function test_admin_can_list_roles(): void
    {
        Role::factory()->count(3)->create();

        $response = $this->actingAs($this->makeAdmin(), 'sanctum')
            ->getJson('/api/admin/roles');

        $response->assertOk()
            ->assertJsonStructure([
                ['id', 'name', 'description', 'isSystemRole', 'permissions', 'users_count', 'created_at'],
            ]);
    }

    public function test_non_admin_cannot_list_roles(): void
    {
        $response = $this->actingAs($this->makeRegularUser(), 'sanctum')
            ->getJson('/api/admin/roles');

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_access_roles(): void
    {
        $response = $this->getJson('/api/admin/roles');

        $response->assertUnauthorized();
    }

    public function test_admin_can_create_role(): void
    {
        $response = $this->actingAs($this->makeAdmin(), 'sanctum')
            ->postJson('/api/admin/roles', [
                'name' => 'moderator',
                'description' => 'Content moderator',
            ]);

        $response->assertCreated()
            ->assertJsonStructure(['message', 'role']);

        $this->assertDatabaseHas('roles', ['name' => 'moderator']);
    }

    public function test_admin_can_view_single_role(): void
    {
        $role = Role::factory()->create();

        $response = $this->actingAs($this->makeAdmin(), 'sanctum')
            ->getJson("/api/admin/roles/{$role->id}");

        $response->assertOk()
            ->assertJsonStructure(['id', 'name', 'description', 'isSystemRole', 'permissions', 'users_count', 'created_at']);
    }

    public function test_admin_can_update_role(): void
    {
        $role = Role::factory()->create(['name' => 'editor', 'description' => 'Original desc']);

        $response = $this->actingAs($this->makeAdmin(), 'sanctum')
            ->putJson("/api/admin/roles/{$role->id}", [
                'description' => 'Updated description',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('roles', ['id' => $role->id, 'description' => 'Updated description']);
    }

    public function test_admin_cannot_update_system_role(): void
    {
        $systemRole = Role::create(['name' => 'system', 'isSystemRole' => true]);

        $response = $this->actingAs($this->makeAdmin(), 'sanctum')
            ->putJson("/api/admin/roles/{$systemRole->id}", [
                'description' => 'Trying to update system role',
            ]);

        $response->assertForbidden();
    }

    public function test_admin_can_delete_role(): void
    {
        $role = Role::factory()->create();

        $response = $this->actingAs($this->makeAdmin(), 'sanctum')
            ->deleteJson("/api/admin/roles/{$role->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_admin_cannot_delete_system_role(): void
    {
        $systemRole = Role::create(['name' => 'system', 'isSystemRole' => true]);

        $response = $this->actingAs($this->makeAdmin(), 'sanctum')
            ->deleteJson("/api/admin/roles/{$systemRole->id}");

        $response->assertForbidden();
    }

    public function test_admin_can_assign_role_to_user(): void
    {
        $role = Role::factory()->create();

        $response = $this->actingAs($this->makeAdmin(), 'sanctum')
            ->postJson("/api/admin/roles/{$role->id}/assign", [
                'userId' => User::factory()->create()->id,
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('role_user', [
            'role_id' => $role->id,
        ]);
    }

    public function test_admin_can_remove_role_from_user(): void
    {
        $role = Role::factory()->create();
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $response = $this->actingAs($this->makeAdmin(), 'sanctum')
            ->postJson("/api/admin/roles/{$role->id}/remove", [
                'userId' => $user->id,
            ]);

        $response->assertOk();
        $this->assertDatabaseMissing('role_user', [
            'role_id' => $role->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_system_role_is_read_only(): void
    {
        $systemRole = Role::create(['name' => 'system', 'isSystemRole' => true]);

        $this->assertFalse($systemRole->canAssignRole());
        $this->assertTrue($systemRole->isSystemRole);
    }
}
