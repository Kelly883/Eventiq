<?php

namespace Tests\Feature;

use App\Features\OfflineSync\Models\OfflineSyncInboxItem;
use App\Features\PushNotifications\Models\PushNotificationDevice;
use App\Models\User;
use App\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'secret-password-123';

    private function makeUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'passwordHash' => Hash::make(self::PASSWORD),
        ], $overrides));
    }

    private function registerPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Jane Organizer',
            'email' => 'jane@example.test',
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
        ], $overrides);
    }

    public function test_register_creates_user_with_hashed_password(): void
    {
        $response = $this->postJson('/api/auth/register', $this->registerPayload());

        $response->assertOk()
            ->assertJsonPath('user.email', 'jane@example.test');

        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.test',
        ]);

        $user = User::where('email', 'jane@example.test')->firstOrFail();

        // Regression: the auth column is passwordHash (not `password`), and
        // the stored value must be bcrypt-hashed, never the plaintext.
        $this->assertNotSame(self::PASSWORD, $user->passwordHash);
        $this->assertTrue(Hash::check(self::PASSWORD, $user->passwordHash));
    }

    public function test_register_auto_authenticates_user(): void
    {
        $this->postJson('/api/auth/register', $this->registerPayload())->assertOk();

        $me = $this->getJson('/api/auth/me');

        $me->assertOk()
            ->assertJsonPath('email', 'jane@example.test');
    }

    public function test_register_rejects_duplicate_email(): void
    {
        $this->makeUser(['email' => 'jane@example.test']);

        $response = $this->postJson('/api/auth/register', $this->registerPayload());

        $response->assertJsonValidationErrors(['email']);
    }

    public function test_register_rejects_short_password(): void
    {
        $response = $this->postJson('/api/auth/register', $this->registerPayload([
            'password' => 'short',
            'password_confirmation' => 'short',
        ]));

        $response->assertJsonValidationErrors(['password']);
    }

    public function test_register_rejects_non_matching_password_confirmation(): void
    {
        $response = $this->postJson('/api/auth/register', $this->registerPayload([
            'password_confirmation' => 'different-password-123',
        ]));

        $response->assertJsonValidationErrors(['password']);
    }

    public function test_login_authenticates_session_with_password_hash(): void
    {
        $this->makeUser(['email' => 'login@example.test']);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'login@example.test',
            'password' => self::PASSWORD,
        ]);

        $response->assertOk()
            ->assertJsonPath('user.email', 'login@example.test');

        $this->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('email', 'login@example.test');
    }

    public function test_login_rejects_wrong_password(): void
    {
        $this->makeUser(['email' => 'login@example.test']);

        $this->postJson('/api/auth/login', [
            'email' => 'login@example.test',
            'password' => 'wrong-password-123',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // The failed attempt must not have authenticated the session.
        $this->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_login_rejects_unknown_email(): void
    {
        $this->postJson('/api/auth/login', [
            'email' => 'nobody@example.test',
            'password' => self::PASSWORD,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_logout_invalidates_session_and_purges_offline_data(): void
    {
        $this->postJson('/api/auth/register', $this->registerPayload([
            'email' => 'logout@example.test',
        ]))->assertOk();

        $user = User::where('email', 'logout@example.test')->firstOrFail();

        $device = PushNotificationDevice::create([
            'user_id' => $user->id,
            'token' => strtolower(bin2hex(random_bytes(32))),
            'provider' => 'web',
            'device_type' => 'web',
            'offline_enabled' => true,
        ]);

        // Two queued offline ops for that device plus one already applied:
        // logout must orphan-purge the queued ones and keep the applied row.
        foreach (['mut-1', 'mut-2'] as $i => $mutation) {
            OfflineSyncInboxItem::create([
                'client_id' => $device->token,
                'op_type' => 'check_in',
                'entity_id' => 'ticket-' . $i,
                'client_mutation_id' => $mutation,
                'status' => 'queued',
                'payload' => ['action' => 'check_in'],
            ]);
        }

        OfflineSyncInboxItem::create([
            'client_id' => $device->token,
            'op_type' => 'check_in',
            'entity_id' => 'ticket-applied',
            'client_mutation_id' => 'mut-applied',
            'status' => 'applied',
            'payload' => ['action' => 'check_in'],
        ]);

        $loginKey = Auth::guard('web')->getName();
        $sessionIdBefore = app('session.store')->getId();

        $this->assertTrue(app('session.store')->has($loginKey), 'session holds the auth id after register');

        $this->postJson('/api/auth/logout')->assertOk();

        // Session is invalidated at the session level (redirectKey gone,
        // new id). The web guard keeps resolving the buffered user in-process,
        // so asserting guard state here is unreliable — assert the session.
        $this->assertFalse(app('session.store')->has($loginKey), 'session auth id must be removed');
        $this->assertNotSame($sessionIdBefore, app('session.store')->getId(), 'session id must rotate');

        // Device tokens are soft-deleted and queued operations purged.
        $this->assertSoftDeleted('push_notification_devices', ['user_id' => $user->id]);
        $this->assertDatabaseHas('offline_sync_inbox', [
            'client_id' => $device->token,
            'client_mutation_id' => 'mut-applied',
            'status' => 'applied',
        ]);
        $this->assertDatabaseMissing('offline_sync_inbox', [
            'client_id' => $device->token,
            'client_mutation_id' => 'mut-1',
        ]);
        $this->assertDatabaseMissing('offline_sync_inbox', [
            'client_id' => $device->token,
            'client_mutation_id' => 'mut-2',
        ]);
    }

    public function test_forgot_password_creates_reset_token_and_sends_link(): void
    {
        Notification::fake();

        $user = $this->makeUser(['email' => 'forgot@example.test']);

        $this->postJson('/api/auth/forgot-password', [
            'email' => 'forgot@example.test',
        ])->assertOk();

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_reset_link_points_at_the_spa_reset_page(): void
    {
        $user = $this->makeUser(['email' => 'spa@example.test']);

        $mail = (new ResetPasswordNotification('reset-token-123'))->toMail($user);

        // Regression: the framework default builds a route('password.reset')
        // (Blade) URL that 500s in this API-only backend. The link must reach
        // the SPA's /reset-password page carrying token + email in the query.
        $this->assertStringContainsString('/reset-password?token=reset-token-123', $mail->actionUrl);
        $this->assertStringContainsString('email=spa%40example.test', $mail->actionUrl);
        $this->assertStringNotContainsString('/password/reset', $mail->actionUrl);
    }

    public function test_forgot_password_rejects_unknown_email(): void
    {
        $this->postJson('/api/auth/forgot-password', [
            'email' => 'missing@example.test',
        ])->assertStatus(400);
    }

    public function test_reset_password_updates_the_password_hash(): void
    {
        $user = $this->makeUser(['email' => 'reset@example.test']);

        $token = Password::broker()->createToken($user);

        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'reset@example.test',
            'token' => $token,
            'password' => 'brand-new-password-456',
            'password_confirmation' => 'brand-new-password-456',
        ]);

        $response->assertOk();

        $user->refresh();

        // Regression for the passwordHash (not `password`) column: the write
        // must land on passwordHash and be hashed, never stored in plaintext.
        $this->assertNotSame('brand-new-password-456', $user->passwordHash);
        $this->assertTrue(Hash::check('brand-new-password-456', $user->passwordHash));
        $this->assertFalse(Hash::check(self::PASSWORD, $user->passwordHash));

        // The new password now works for a real login.
        $this->postJson('/api/auth/login', [
            'email' => 'reset@example.test',
            'password' => 'brand-new-password-456',
        ])->assertOk();
    }
}