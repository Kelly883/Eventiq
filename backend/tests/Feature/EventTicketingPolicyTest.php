<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EventTicketingPolicyTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function non_organizer_cannot_patch_event_ticketing(): void
    {
        // Owner organizer and event
        $ownerUser = User::factory()->create(['role' => 'organizer']);
        $ownerOrganizer = Organizer::factory()->create(['user_id' => $ownerUser->id]);
        $event = Event::factory()->create(['organizer_id' => $ownerOrganizer->id]);

        // Another organizer attempting update
        $otherUser = User::factory()->create(['role' => 'organizer']);
        $token = $otherUser->createToken('test-token')->plainTextToken;

        // Clear rate limiter to avoid interference
        RateLimiter::clear('ticket-tier-update');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson("/api/organizer/events/{$event->id}/ticketing", [
                'ticketTiers' => [
                    ['name' => 'New Tier', 'price' => 100, 'quantity' => 10],
                ],
            ]);

        $response->assertStatus(403);
    }
}
?>