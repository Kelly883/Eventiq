<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tight feedback loop for the "Server Error" seen on the Settings →
 * Delivery Preferences page (Push Notifications section).
 *
 * Drives GET/PUT /api/push-notifications/preferences exactly as the SPA
 * does (custom bearer token from /api/auth/login) and asserts the real
 * response the browser received.
 */
class PushPreferencesReproTest extends TestCase
{
    use RefreshDatabase;

    private function loginAndGetToken(): array
    {
        $email = 'repro-push-' . uniqid() . '@example.com';
        $this->postJson('/api/auth/register', [
            'name' => 'Push Repro',
            'email' => $email,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $email,
            'password' => 'Password123!',
        ]);

        $body = $response->json();
        $token = $body['token'] ?? $body['data']['token'] ?? $body['access_token'] ?? null;

        return [$email, $token];
    }

    public function test_get_push_preferences_returns_200_with_camel_case_payload(): void
    {
        [$email, $token] = $this->loginAndGetToken();
        $this->assertNotNull($token, 'Login must issue a bearer token');

        $response = $this->withToken($token)->getJson('/api/push-notifications/preferences');

        // Repro assertion: the SPA currently receives {"message":"Server Error"} 500
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'pushNotificationsEnabled',
                'pushOrderConfirmation',
                'pushEventReminder',
                'pushCheckinAlert',
                'pushPromotionalOffers',
            ],
        ]);
    }

    public function test_put_push_preferences_updates_and_returns_preferences(): void
    {
        [$email, $token] = $this->loginAndGetToken();

        $response = $this->withToken($token)->putJson('/api/push-notifications/preferences', [
            'push_notifications_enabled' => true,
            'push_order_confirmation' => true,
            'push_event_reminder' => false,
        ]);

        $response->assertStatus(200);
        $this->assertTrue((bool) $response->json('data.pushNotificationsEnabled'));
        $this->assertFalse((bool) $response->json('data.pushEventReminder'));
    }

    public function test_unauthenticated_request_returns_401_json_not_html(): void
    {
        $response = $this->getJson('/api/push-notifications/preferences');

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthorized']);
    }
}
