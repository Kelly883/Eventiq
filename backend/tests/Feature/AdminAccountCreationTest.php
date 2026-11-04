<?php

namespace Tests\Feature;

use App\Features\Compliance\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminAccountCreationTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            ['description' => 'Administrator', 'isSystemRole' => true]
        );
        $user = User::factory()->create(['role' => 'admin']);
        $user->roles()->sync([$role->id]);

        return $user;
    }

    public function test_admin_can_create_multiple_additional_admin_accounts(): void
    {
        Sanctum::actingAs($this->makeAdmin());

        foreach (['first', 'second'] as $name) {
            $response = $this->postJson('/api/admin/users/admin-accounts', [
                'name' => ucfirst($name) . ' New Admin',
                'email' => "{$name}.admin@example.test",
                'password' => 'SecurePassword123!',
                'password_confirmation' => 'SecurePassword123!',
            ]);

            $response->assertCreated()
                ->assertJsonPath('user.role', 'admin')
                ->assertJsonMissingPath('user.password');

            $newAdmin = User::where('email', "{$name}.admin@example.test")->firstOrFail();
            $this->assertTrue($newAdmin->hasRole('admin'));
            $this->assertSame('active', $newAdmin->status);
            $this->assertTrue(Hash::check('SecurePassword123!', $newAdmin->passwordHash));
        }

        $this->assertSame(2, AuditLog::where('action', 'admin_account_created')->count());

        $this->postJson('/api/auth/login', [
            'email' => 'first.admin@example.test',
            'password' => 'SecurePassword123!',
        ])->assertOk()
            ->assertJsonPath('user.role', 'admin');
    }

    public function test_admin_account_creation_requires_authentication(): void
    {
        $this->postJson('/api/admin/users/admin-accounts', [
            'name' => 'New Admin',
            'email' => 'new.admin@example.test',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
        ])->assertUnauthorized();
    }

    public function test_admin_account_creation_requires_admin_role(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/admin/users/admin-accounts', [
            'name' => 'New Admin',
            'email' => 'new.admin@example.test',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
        ])->assertForbidden();
    }

    public function test_admin_account_creation_validates_fields_and_duplicate_email(): void
    {
        Sanctum::actingAs($this->makeAdmin());

        $this->postJson('/api/admin/users/admin-accounts', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password']);

        User::factory()->create(['email' => 'existing@example.test']);

        $this->postJson('/api/admin/users/admin-accounts', [
            'name' => 'New Admin',
            'email' => 'existing@example.test',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }
}
