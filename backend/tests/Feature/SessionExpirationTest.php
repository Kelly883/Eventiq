<?php

namespace Tests\Feature;

use App\Models\Session;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SessionExpirationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'passwordHash' => bcrypt('password123'),
        ]);
    }

    private function createBearerToken(Session $session): string
    {
        // We need to reverse-engineer the plain token from the hash — but we can't.
        // Instead, we create a known plain token and store its hash.
        $plainToken = 'test_token_' . Str::random(32);
        $session->update(['token' => hash('sha256', $plainToken)]);
        return $plainToken;
    }

    /** TEST 1: Valid session allows access */
    public function test_valid_session_allows_access(): void
    {
        $token = $this->user->createToken('test')->plainTextToken;
        $resp = $this->withToken($token)->getJson('/api/auth/me');
        $resp->assertStatus(200);
    }

    /** TEST 2: Idle-expired session returns 401 */
    public function test_idle_expired_session_returns_401(): void
    {
        $plainToken = 'test_token_' . Str::random(32);
        Session::create([
            'userId' => $this->user->id,
            'token' => hash('sha256', $plainToken),
            'expiresAt' => now()->addDays(7),
            'lastActivityAt' => now()->subMinutes(31),
        ]);

        $resp = $this->withHeaders(['Authorization' => 'Bearer ' . $plainToken])
            ->getJson('/api/auth/me');

        $resp->assertStatus(401);
    }

    /** TEST 3: Revoked session returns 401 */
    public function test_revoked_session_returns_401(): void
    {
        $plainToken = 'test_token_' . Str::random(32);
        $session = Session::create([
            'userId' => $this->user->id,
            'token' => hash('sha256', $plainToken),
            'expiresAt' => now()->addDays(7),
            'lastActivityAt' => now(),
        ]);
        $session->revoke();

        $resp = $this->withHeaders(['Authorization' => 'Bearer ' . $plainToken])
            ->getJson('/api/auth/me');

        $resp->assertStatus(401);
    }

    /** TEST 4: Valid session records activity */
    public function test_valid_session_records_activity(): void
    {
        $plainToken = 'test_token_' . Str::random(32);
        $session = Session::create([
            'userId' => $this->user->id,
            'token' => hash('sha256', $plainToken),
            'expiresAt' => now()->addDays(7),
            'lastActivityAt' => now()->subMinutes(5),
        ]);

        $beforeActivity = $session->lastActivityAt;

        $resp = $this->withHeaders(['Authorization' => 'Bearer ' . $plainToken])
            ->getJson('/api/auth/me');

        $resp->assertStatus(200);
        $session->refresh();
        $this->assertGreaterThan($beforeActivity, $session->lastActivityAt);
    }

    /** TEST 5: Expired session returns 401 */
    public function test_expired_session_returns_401(): void
    {
        $plainToken = 'test_token_' . Str::random(32);
        Session::create([
            'userId' => $this->user->id,
            'token' => hash('sha256', $plainToken),
            'expiresAt' => now()->subMinute(),
            'lastActivityAt' => now(),
        ]);

        $resp = $this->withHeaders(['Authorization' => 'Bearer ' . $plainToken])
            ->getJson('/api/auth/me');

        $resp->assertStatus(401);
    }

    /** TEST 6: Session idle timeout is configurable */
    public function test_idle_timeout_is_configurable(): void
    {
        config(['sanctum.idle_timeout' => 0]);

        $plainToken = 'test_token_' . Str::random(32);
        Session::create([
            'userId' => $this->user->id,
            'token' => hash('sha256', $plainToken),
            'expiresAt' => now()->addDays(7),
            'lastActivityAt' => now()->subDays(1),
        ]);

        $resp = $this->withHeaders(['Authorization' => 'Bearer ' . $plainToken])
            ->getJson('/api/auth/me');

        $resp->assertStatus(200);
    }

    /** TEST 7: Idle timeout is checked on every request */
    public function test_idle_timeout_checked_on_every_request(): void
    {
        $plainToken = 'test_token_' . Str::random(32);
        $session = Session::create([
            'userId' => $this->user->id,
            'token' => hash('sha256', $plainToken),
            'expiresAt' => now()->addDays(7),
            'lastActivityAt' => now(),
        ]);

        $resp = $this->withHeaders(['Authorization' => 'Bearer ' . $plainToken])
            ->getJson('/api/auth/me');
        $resp->assertStatus(200);

        $session->update(['lastActivityAt' => now()->subMinutes(31)]);

        $resp = $this->withHeaders(['Authorization' => 'Bearer ' . $plainToken])
            ->getJson('/api/auth/me');
        $resp->assertStatus(401);
    }

    /** TEST 8: Session created with lastActivityAt */
    public function test_session_created_with_last_activity_at(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => $this->user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $this->assertNotNull($response->json('token'));

        $session = Session::where('userId', $this->user->id)->first();
        $this->assertNotNull($session);
        $this->assertNotNull($session->lastActivityAt);
    }

    /** TEST 9: Sanctum cannot bypass idle timeout */
    public function test_sanctum_cannot_bypass_idle_timeout(): void
    {
        $plainToken = 'test_token_' . Str::random(32);
        Session::create([
            'userId' => $this->user->id,
            'token' => hash('sha256', $plainToken),
            'expiresAt' => now()->addDays(7),
            'lastActivityAt' => now()->subMinutes(31),
        ]);

        $resp = $this->withHeaders(['Authorization' => 'Bearer ' . $plainToken])
            ->getJson('/api/auth/me');

        $resp->assertStatus(401);
    }

    /** TEST 10: Fresh login resets idle timer */
    public function test_fresh_login_resets_idle_timer(): void
    {
        $plainToken = 'test_token_' . Str::random(32);
        Session::create([
            'userId' => $this->user->id,
            'token' => hash('sha256', $plainToken),
            'expiresAt' => now()->addDays(7),
            'lastActivityAt' => now()->subMinutes(31),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $this->user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $newToken = $response->json('token');
        $this->assertNotNull($newToken);

        $resp = $this->withHeaders(['Authorization' => 'Bearer ' . $newToken])
            ->getJson('/api/auth/me');
        $resp->assertStatus(200);
    }

    /** TEST 11: Legacy session without lastActivityAt is handled gracefully */
    public function test_legacy_session_without_last_activity_at(): void
    {
        $plainToken = 'test_token_' . Str::random(32);
        Session::create([
            'userId' => $this->user->id,
            'token' => hash('sha256', $plainToken),
            'expiresAt' => now()->addDays(7),
            'lastActivityAt' => null,
        ]);

        $resp = $this->withHeaders(['Authorization' => 'Bearer ' . $plainToken])
            ->getJson('/api/auth/me');

        $resp->assertStatus(200);
    }

    /** TEST 12: Activity extends session lifetime (sliding window) */
    public function test_activity_extends_session_lifetime(): void
    {
        $plainToken = 'test_token_' . Str::random(32);
        $session = Session::create([
            'userId' => $this->user->id,
            'token' => hash('sha256', $plainToken),
            'expiresAt' => now()->addMinutes(55),
            'lastActivityAt' => now(),
        ]);

        $resp = $this->withHeaders(['Authorization' => 'Bearer ' . $plainToken])
            ->getJson('/api/auth/me');

        $resp->assertStatus(200);
        $session->refresh();
        $this->assertGreaterThan(now()->addMinutes(55), $session->expiresAt);
    }
}
