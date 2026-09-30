<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_setup_creates_first_admin_when_none_exists(): void
    {
        $response = $this->postJson('/api/auth/admin-setup', [
            'name' => 'Platform Admin',
            'email' => 'admin@eventiq.test',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Administrator account created successfully. You may now sign in.')
            ->assertJsonPath('user.name', 'Platform Admin')
            ->assertJsonPath('user.email', 'admin@eventiq.test')
            ->assertJsonPath('user.role', 'admin');

        $this->assertDatabaseHas('users', [
            'email' => 'admin@eventiq.test',
            'role' => 'admin',
        ]);

        $adminRole = Role::where('name', 'admin')->first();
        $this->assertNotNull($adminRole);

        $user = User::where('email', 'admin@eventiq.test')->first();
        $this->assertTrue($user->hasRole('admin'));
    }

    public function test_admin_setup_returns_404_when_admin_already_exists(): void
    {
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['description' => 'Administrator', 'isSystemRole' => true]
        );

        User::unguarded(fn () => User::create([
            'name' => 'Existing Admin',
            'email' => 'existing@eventiq.test',
            'passwordHash' => bcrypt('password'),
            'role' => 'admin',
            'emailVerified' => true,
            'status' => 'active',
        ]));

        $response = $this->postJson('/api/auth/admin-setup', [
            'name' => 'Second Admin',
            'email' => 'second@eventiq.test',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('message', 'Not found.');

        $this->assertDatabaseMissing('users', [
            'email' => 'second@eventiq.test',
        ]);
    }

    public function test_admin_setup_validates_required_fields(): void
    {
        $response = $this->postJson('/api/auth/admin-setup', []);

        $response->assertStatus(422)
            ->assertJsonPath('errors.name.0', 'The name field is required.')
            ->assertJsonPath('errors.email.0', 'The email field is required.')
            ->assertJsonPath('errors.password.0', 'The password field is required.');
    }

    public function test_admin_setup_rejects_short_password(): void
    {
        $response = $this->postJson('/api/auth/admin-setup', [
            'name' => 'Admin',
            'email' => 'admin@eventiq.test',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.password.0', 'The password field must be at least 8 characters.');
    }

    public function test_admin_setup_rejects_password_mismatch(): void
    {
        $response = $this->postJson('/api/auth/admin-setup', [
            'name' => 'Admin',
            'email' => 'admin@eventiq.test',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'DifferentPass123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.password_confirmation.0', 'The password confirmation does not match.');
    }

    public function test_admin_setup_rejects_duplicate_email(): void
    {
        User::unguarded(fn () => User::create([
            'name' => 'Existing User',
            'email' => 'existing@eventiq.test',
            'passwordHash' => bcrypt('password'),
            'role' => 'attendee',
        ]));

        $response = $this->postJson('/api/auth/admin-setup', [
            'name' => 'Admin',
            'email' => 'existing@eventiq.test',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'The email has already been taken.');
    }

    public function test_admin_setup_get_endpoint_returns_required_true_when_no_admin(): void
    {
        $response = $this->getJson('/api/auth/admin-setup');

        $response->assertStatus(200)
            ->assertJsonPath('required', true);
    }

    public function test_admin_setup_get_endpoint_returns_required_false_when_admin_exists(): void
    {
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['description' => 'Administrator', 'isSystemRole' => true]
        );

        User::unguarded(fn () => User::create([
            'name' => 'Admin',
            'email' => 'admin@eventiq.test',
            'passwordHash' => bcrypt('password'),
            'role' => 'admin',
        ]));

        $response = $this->getJson('/api/auth/admin-setup');

        $response->assertStatus(200)
            ->assertJsonPath('required', false);
    }

    public function test_admin_setup_is_rate_limited(): void
    {
        $limiterKey = 'admin-setup:127.0.0.1';

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/admin-setup', [
                'name' => 'Admin',
                'email' => "admin{$i}@eventiq.test",
                'password' => 'SecurePass123!',
                'password_confirmation' => 'SecurePass123!',
            ]);
        }

        $response = $this->postJson('/api/auth/admin-setup', [
            'name' => 'Admin',
            'email' => 'admin6@eventiq.test',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $response->assertStatus(429);
    }
}
