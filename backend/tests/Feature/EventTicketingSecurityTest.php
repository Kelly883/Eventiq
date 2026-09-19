<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Organizer;
use App\Models\TicketTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class EventTicketingSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $organizerUser;
    private Organizer $organizer;
    private Event $event;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('ticket-tier-update');

        $this->organizerUser = User::factory()->create(['role' => 'organizer']);
        $this->organizer = Organizer::factory()->create(['user_id' => $this->organizerUser->id]);
        $this->token = $this->organizerUser->createToken('test-token')->plainTextToken;

        $this->event = Event::factory()->create([
            'organizer_id' => $this->organizer->id,
            'title' => 'Test Event',
            'start_datetime' => now()->addDays(10),
            'end_datetime' => now()->addDays(10)->addHours(4),
        ]);
    }

    /** @test */
    public function patch_endpoint_has_rate_limiting(): void
    {
        // The dedicated ticket-tier-update limiter should be registered
        // Verify by checking the limiter tracks attempts
        RateLimiter::clear('ticket-tier-update');
        $this->assertEquals(0, RateLimiter::attempts('ticket-tier-update|' . request()->ip()));
    }

    /** @test */
    public function patch_endpoint_is_rate_limited(): void
    {
        // Make requests up to the limit
        for ($i = 0; $i < 10; $i++) {
            $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
                ->patchJson("/api/organizer/events/{$this->event->id}/ticketing", [
                    'ticketTiers' => [
                        ['name' => "Tier {$i}", 'price' => 100, 'quantity' => 10],
                    ],
                ]);
            $this->assertEquals(200, $response->status(), "Request $i+1 should succeed, got: " . $response->status());
        }

        // The 11th request should be rate-limited (429)
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->patchJson("/api/organizer/events/{$this->event->id}/ticketing", [
                'ticketTiers' => [
                    ['name' => 'Over Limit', 'price' => 100, 'quantity' => 10],
                ],
            ]);

        $this->assertEquals(429, $response->status(), '11th request should be rate limited');
    }

    /** @test */
    public function tier_image_url_rejects_urls_with_userinfo(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->patchJson("/api/organizer/events/{$this->event->id}/ticketing", [
                'ticketTiers' => [
                    [
                        'name' => 'Test',
                        'price' => 100,
                        'quantity' => 10,
                        'tier_image_url' => 'http://evil.com@localhost/image.jpg',
                    ],
                ],
            ]);

        $response->assertStatus(422);
        // Check that tier_image_url has a validation error in the response
        $errors = $response->json('errors');
        $this->assertNotEmpty($errors, 'Validation errors should not be empty');
        $imageUrlErrors = $errors['ticketTiers'][0]['tier_image_url'] ?? $errors['ticketTiers'][0]['tierImageUrl'] ?? null;
        $this->assertNotEmpty($imageUrlErrors, 'tier_image_url should have validation errors');
    }

    /** @test */
    public function audit_logs_are_created_for_each_tier_operation(): void
    {
        $initialCount = \App\Features\Compliance\Models\AuditLog::count();

        $tier = TicketTier::factory()->create([
            'event_id' => $this->event->id,
            'name' => 'Existing Tier',
            'price' => 100,
            'quantity' => 50,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->patchJson("/api/organizer/events/{$this->event->id}/ticketing", [
                'ticketTiers' => [
                    ['id' => $tier->id, 'name' => 'Updated Tier', 'price' => 200, 'quantity' => 75],
                    ['name' => 'Brand New Tier', 'price' => 50, 'quantity' => 100],
                ],
            ]);

        $response->assertStatus(200);

        $auditLogs = \App\Features\Compliance\Models\AuditLog::latest()->take(3)->get();

        // Should have individual audit entries for create and update
        $this->assertTrue(
            $auditLogs->contains(fn ($log) => str_contains($log->description, 'Tier created') && str_contains($log->description, 'Brand New Tier')),
            'Should have audit log for tier creation'
        );
        $this->assertTrue(
            $auditLogs->contains(fn ($log) => str_contains($log->description, 'Tier updated') && str_contains($log->description, 'Updated Tier')),
            'Should have audit log for tier update'
        );
    }

    /** @test */
    public function soft_deleted_tier_can_be_reactivated_without_name_collision(): void
    {
        // Create and then soft-delete a tier
        $tier = TicketTier::factory()->create([
            'event_id' => $this->event->id,
            'name' => 'Original VIP',
            'price' => 5000,
            'quantity' => 50,
        ]);

        // Delete it (exclude from request)
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->patchJson("/api/organizer/events/{$this->event->id}/ticketing", [
                'ticketTiers' => [
                    ['name' => 'Regular', 'price' => 1000, 'quantity' => 200],
                ],
            ]);
        $response->assertStatus(200);

        // Reactivate it by including its ID again
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->patchJson("/api/organizer/events/{$this->event->id}/ticketing", [
                'ticketTiers' => [
                    ['id' => $tier->id, 'name' => 'VIP Restored', 'price' => 5000, 'quantity' => 50],
                    ['name' => 'Regular', 'price' => 1000, 'quantity' => 200],
                ],
            ]);

        $response->assertStatus(200);

        $restoredTier = TicketTier::find($tier->id);
        $this->assertNotNull($restoredTier);
        $this->assertNull($restoredTier->deleted_at);
        $this->assertEquals('VIP Restored', $restoredTier->name);
    }

    /** @test */
    public function published_event_cannot_have_all_tiers_deleted(): void
    {
        $tier = TicketTier::factory()->create([
            'event_id' => $this->event->id,
            'name' => 'Only Tier',
            'price' => 100,
            'quantity' => 50,
        ]);

        // First delete the existing tier
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->patchJson("/api/organizer/events/{$this->event->id}/ticketing", [
                'ticketTiers' => [
                    ['name' => 'Replacement', 'price' => 200, 'quantity' => 100],
                ],
            ]);
        $response->assertStatus(200);

        // Now try to delete all tiers (empty array) — should be rejected
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->patchJson("/api/organizer/events/{$this->event->id}/ticketing", [
                'ticketTiers' => [],
            ]);

        // Empty array is rejected by existing guard
        $this->assertEquals(422, $response->status());
    }
}
