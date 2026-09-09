<?php

namespace Tests\Feature;

use App\Models\PasswordResetToken;
use App\Models\Session;
use App\Models\User;
use App\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
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
        ], $overrides);
    }

    private function createResetToken(User $user, string $plainToken, array $overrides = []): PasswordResetToken
    {
        return PasswordResetToken::create(array_merge([
            'userId' => $user->id,
            'token' => Hash::make($plainToken),
            'token_hash' => hash('sha256', $plainToken),
            'expiresAt' => now()->addHour(),
        ], $overrides));
    }

    public function test_register_creates_user_with_hashed_password(): void
    {
        $response = $this->postJson('/api/auth/register', $this->registerPayload());

        $response->assertStatus(201)
            ->assertJsonPath('email', 'jane@example.test')
            ->assertJsonPath('role', 'attendee')
            ->assertJsonStructure(['id', 'email', 'name', 'role']);

        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.test',
            'role' => 'attendee',
        ]);

        $user = User::where('email', 'jane@example.test')->firstOrFail();

        $this->assertNotSame(self::PASSWORD, $user->passwordHash);
        $this->assertTrue(Hash::check(self::PASSWORD, $user->passwordHash));

        $this->assertArrayNotHasKey('passwordHash', $response->json());
        $this->assertArrayNotHasKey('password', $response->json());
    }

    public function test_register_does_not_issue_a_token(): void
    {
        $this->postJson('/api/auth/register', $this->registerPayload())->assertStatus(201);
        $this->assertDatabaseCount('sessions', 0);
    }

    public function test_register_rejects_duplicate_email(): void
    {
        $this->makeUser(['email' => 'jane@example.test']);

        $response = $this->postJson('/api/auth/register', $this->registerPayload());

        $response->assertStatus(409)
            ->assertJsonPath('message', 'This email is already registered');
    }

    public function test_register_rejects_short_password(): void
    {
        $response = $this->postJson('/api/auth/register', $this->registerPayload([
            'password' => 'short',
        ]));

        $response->assertStatus(422)->assertJsonValidationErrors(['password']);
    }

    public function test_register_rejects_short_name(): void
    {
        $response = $this->postJson('/api/auth/register', $this->registerPayload([
            'name' => 'A',
        ]));

        $response->assertStatus(422)->assertJsonValidationErrors(['name']);
    }

    public function test_login_returns_token_and_user(): void
    {
        $this->makeUser(['email' => 'login@example.test']);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'login@example.test',
            'password' => self::PASSWORD,
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'email', 'name', 'role']])
            ->assertJsonPath('user.email', 'login@example.test');

        $user = User::where('email', 'login@example.test')->firstOrFail();
        $session = Session::where('userId', $user->id)->first();

        $this->assertNotNull($session);
        $this->assertTrue(
            $session->expiresAt->between(now()->addDays(6)->endOfDay(), now()->addDays(7)->endOfDay()),
            'Session expiry should be approximately 7 days from now'
        );
    }

    public function test_login_updates_last_login_at(): void
    {
        $this->makeUser(['email' => 'login@example.test']);

        $this->postJson('/api/auth/login', [
            'email' => 'login@example.test',
            'password' => self::PASSWORD,
        ])->assertOk();

        $user = User::where('email', 'login@example.test')->firstOrFail();
        $this->assertNotNull($user->lastLoginAt);
        $this->assertTrue($user->lastLoginAt->isBetween(now()->subSecond(), now()->addSecond()));
    }

    public function test_login_rejects_unknown_email(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nobody@example.test',
            'password' => self::PASSWORD,
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('message', 'Invalid email or password');
    }

    public function test_login_rejects_wrong_password(): void
    {
        $this->makeUser(['email' => 'login@example.test']);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'login@example.test',
            'password' => 'wrong-password-456',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('message', 'Invalid email or password');
    }

    public function test_login_rate_limits_after_five_attempts(): void
    {
        $this->makeUser(['email' => 'login@example.test']);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', [
                'email' => 'login@example.test',
                'password' => 'wrong-password',
            ])->assertStatus(401);
        }

        $this->postJson('/api/auth/login', [
            'email' => 'login@example.test',
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }

    public function test_bearer_token_authenticates_me(): void
    {
        $this->makeUser(['email' => 'bearer@example.test']);

        $login = $this->postJson('/api/auth/login', [
            'email' => 'bearer@example.test',
            'password' => self::PASSWORD,
        ])->assertOk();

        $token = $login->json('token');
        $this->assertNotEmpty($token);

        // The Bearer token grants access to the protected /auth/me endpoint.
        $me = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/auth/me');

        $me->assertOk()
            ->assertJsonPath('email', 'bearer@example.test');
    }

    public function test_bearer_token_rejects_invalid_token(): void
    {
        $this->withHeader('Authorization', 'Bearer invalid-token-xyz')
            ->getJson('/api/auth/me')
            ->assertUnauthorized();
    }

    public function test_bearer_token_rejects_missing_header(): void
    {
        $this->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_bearer_token_revoked_after_logout(): void
    {
        $this->makeUser(['email' => 'logout-bearer@example.test']);

        $login = $this->postJson('/api/auth/login', [
            'email' => 'logout-bearer@example.test',
            'password' => self::PASSWORD,
        ])->assertOk();

        $token = $login->json('token');

        // Logout with the Bearer token revokes the session.
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/auth/logout')
            ->assertOk();

        // The same token no longer authenticates.
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/auth/me')
            ->assertUnauthorized();
    }

    public function test_forgot_password_returns_generic_message_for_any_email(): void
    {
        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'missing@example.test',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'If an account exists, a reset link has been sent');
    }

    public function test_forgot_password_creates_reset_token_and_sends_link(): void
    {
        Notification::fake();

        $user = $this->makeUser(['email' => 'forgot@example.test']);

        $this->postJson('/api/auth/forgot-password', [
            'email' => 'forgot@example.test',
        ])->assertOk();

        Notification::assertSentTo($user, ResetPasswordNotification::class);

        $this->assertDatabaseHas('password_reset_tokens', [
            'userId' => $user->id,
            'usedAt' => null,
        ]);

        $token = PasswordResetToken::where('userId', $user->id)->firstOrFail();
        $this->assertNotNull($token->token_hash, 'A sha-256 lookup hash must be stored');
        $this->assertTrue(
            $token->expiresAt->between(now()->addMinutes(59)->endOfMinute(), now()->addHour()->endOfMinute()),
            'Reset token expiry should be approximately 1 hour from now'
        );
    }

    public function test_forgot_password_rate_limits_per_ip(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/auth/forgot-password', [
                'email' => "user{$i}@example.test",
            ])->assertOk();
        }

        $this->postJson('/api/auth/forgot-password', [
            'email' => 'user4@example.test',
        ])->assertStatus(429);
    }

    public function test_forgot_password_rate_limits_per_email(): void
    {
        // Vary the client IP for each attempt so only the per-email limiter
        // (5/hour) can trip — simulating a distributed attack from many IPs
        // against one address. The per-IP limiter (3/hour) stays under.
        for ($i = 0; $i < 5; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => "10.0.0.{$i}"])
                ->postJson('/api/auth/forgot-password', [
                    'email' => 'target@example.test',
                ])->assertOk();
        }

        // 6th attempt from a fresh IP, same email — per-email limit trips.
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.99'])
            ->postJson('/api/auth/forgot-password', [
                'email' => 'target@example.test',
            ])->assertStatus(429);
    }

    public function test_reset_link_points_at_the_spa_reset_page(): void
    {
        $user = $this->makeUser(['email' => 'spa@example.test']);

        $mail = (new ResetPasswordNotification('reset-token-123'))->toMail($user);

        $this->assertStringContainsString('/reset-password?token=reset-token-123', $mail->actionUrl);
        $this->assertStringContainsString('email=spa%40example.test', $mail->actionUrl);
        $this->assertStringNotContainsString('/password/reset', $mail->actionUrl);
    }

    public function test_reset_password_updates_the_password_hash(): void
    {
        $user = $this->makeUser(['email' => 'reset@example.test']);

        $plainToken = 'reset-plain-token-123';
        $this->createResetToken($user, $plainToken);

        $response = $this->postJson('/api/auth/reset-password', [
            'token' => $plainToken,
            'newPassword' => 'brand-new-password-456',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Password reset successfully');

        $user->refresh();

        $this->assertNotSame('brand-new-password-456', $user->passwordHash);
        $this->assertTrue(Hash::check('brand-new-password-456', $user->passwordHash));
        $this->assertFalse(Hash::check(self::PASSWORD, $user->passwordHash));

        $this->postJson('/api/auth/login', [
            'email' => 'reset@example.test',
            'password' => 'brand-new-password-456',
        ])->assertOk();
    }

    public function test_reset_password_rejects_expired_token(): void
    {
        $user = $this->makeUser(['email' => 'reset@example.test']);

        $plainToken = 'expired-token-123';
        $this->createResetToken($user, $plainToken, ['expiresAt' => now()->subMinute()]);

        $response = $this->postJson('/api/auth/reset-password', [
            'token' => $plainToken,
            'newPassword' => 'brand-new-password-456',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('message', 'This link has expired or is invalid');
    }

    public function test_reset_password_rejects_already_used_token(): void
    {
        $user = $this->makeUser(['email' => 'reset@example.test']);

        $plainToken = 'used-token-123';
        $this->createResetToken($user, $plainToken, ['usedAt' => now()]);

        $response = $this->postJson('/api/auth/reset-password', [
            'token' => $plainToken,
            'newPassword' => 'brand-new-password-456',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('message', 'This link has expired or is invalid');
    }

    public function test_reset_password_invalidates_all_existing_sessions(): void
    {
        $user = $this->makeUser(['email' => 'reset@example.test']);

        Session::create([
            'userId' => $user->id,
            'token' => hash('sha256', 'session-one'),
            'expiresAt' => now()->addDays(7),
        ]);
        Session::create([
            'userId' => $user->id,
            'token' => hash('sha256', 'session-two'),
            'expiresAt' => now()->addDays(7),
        ]);

        $plainToken = 'reset-token-123';
        $this->createResetToken($user, $plainToken);

        $this->postJson('/api/auth/reset-password', [
            'token' => $plainToken,
            'newPassword' => 'brand-new-password-456',
        ])->assertOk();

        $this->assertDatabaseMissing('sessions', [
            'userId' => $user->id,
            'revokedAt' => null,
        ]);
    }
}
