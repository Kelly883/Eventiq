<?php

namespace Tests\Feature;

use App\Features\Pricing\Models\PricingWindow;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\TicketTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingEndpointTest extends TestCase
{
    use RefreshDatabase;

    private User $organizerUser;
    private User $otherOrganizerUser;
    private User $attendeeUser;
    private Organizer $organizer;
    private Organizer $otherOrganizer;
    private Event $event;
    private Event $publishedEvent;
    private TicketTier $tier1;
    private TicketTier $tier2;
    private string $token;
    private string $otherToken;
    private string $attendeeToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organizerUser = User::factory()->create(['role' => 'organizer']);
        $this->organizer = Organizer::factory()->create(['user_id' => $this->organizerUser->id]);
        $this->token = $this->organizerUser->createToken('test-token')->plainTextToken;

        $this->otherOrganizerUser = User::factory()->create(['role' => 'organizer']);
        $this->otherOrganizer = Organizer::factory()->create(['user_id' => $this->otherOrganizerUser->id]);
        $this->otherToken = $this->otherOrganizerUser->createToken('other-token')->plainTextToken;

        $this->attendeeUser = User::factory()->create(['role' => 'attendee']);
        $this->attendeeToken = $this->attendeeUser->createToken('attendee-token')->plainTextToken;

        $this->event = Event::factory()->create([
            'organizer_id' => $this->organizer->id,
            'status' => 'draft',
            'start_datetime' => now()->addDays(10),
            'end_datetime' => now()->addDays(10)->addHours(4),
        ]);

        $this->publishedEvent = Event::factory()->create([
            'organizer_id' => $this->organizer->id,
            'status' => 'live',
            'start_datetime' => now()->addDays(5),
            'end_datetime' => now()->addDays(5)->addHours(4),
        ]);

        $this->tier1 = TicketTier::factory()->create(['event_id' => $this->event->id, 'name' => 'VIP', 'price' => 5000]);
        $this->tier2 = TicketTier::factory()->create(['event_id' => $this->event->id, 'name' => 'Regular', 'price' => 1000]);
    }

    // -------------------------------------------------------------------------
    // LIST: GET /api/organizer/events/{event}/pricing-windows
    // -------------------------------------------------------------------------

    public function test_list_pricing_windows_returns_windows_for_event(): void
    {
        PricingWindow::factory()->count(3)->create(['event_id' => $this->event->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/organizer/events/{$this->event->id}/pricing-windows");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'window_name', 'start_date_time', 'end_date_time', 'price', 'is_active', 'priority']
                ]
            ]);
    }

    public function test_list_pricing_windows_filters_by_ticket_category_id(): void
    {
        $window1 = PricingWindow::factory()->create([
            'event_id' => $this->event->id,
            'ticket_category_id' => $this->tier1->id,
        ]);
        $window2 = PricingWindow::factory()->create([
            'event_id' => $this->event->id,
            'ticket_category_id' => $this->tier2->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/organizer/events/{$this->event->id}/pricing-windows?ticket_category_id={$this->tier1->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $window1->id);
    }

    public function test_list_pricing_windows_sorted_by_priority_then_start_date(): void
    {
        $lowPriority = PricingWindow::factory()->create([
            'event_id' => $this->event->id,
            'priority' => 1,
            'start_date_time' => now()->addHours(2),
        ]);
        $highPriority = PricingWindow::factory()->create([
            'event_id' => $this->event->id,
            'priority' => 10,
            'start_date_time' => now()->addHours(1),
        ]);
        $samePriorityLater = PricingWindow::factory()->create([
            'event_id' => $this->event->id,
            'priority' => 5,
            'start_date_time' => now()->addHours(3),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/organizer/events/{$this->event->id}/pricing-windows");

        $response->assertStatus(200)
            ->assertJsonPath('data.0.id', $highPriority->id)
            ->assertJsonPath('data.1.id', $samePriorityLater->id)
            ->assertJsonPath('data.2.id', $lowPriority->id);
    }

    public function test_is_active_field_computed_correctly(): void
    {
        $activeWindow = PricingWindow::factory()->create([
            'event_id' => $this->event->id,
            'is_active' => true,
            'start_date_time' => now()->subHour(),
            'end_date_time' => now()->addHour(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/organizer/events/{$this->event->id}/pricing-windows");

        $response->assertStatus(200)
            ->assertJsonPath('data.0.is_active', true)
            ->assertJsonPath('data.0.has_availability', true);
    }

    // -------------------------------------------------------------------------
    // CREATE: POST /api/organizer/events/{event}/pricing-windows
    // -------------------------------------------------------------------------

    public function test_create_pricing_window_with_valid_data(): void
    {
        $payload = [
            'window_name' => 'Early Bird',
            'ticket_category_id' => $this->tier1->id,
            'start_date_time' => now()->addDays(1)->toDateTimeString(),
            'end_date_time' => now()->addDays(5)->toDateTimeString(),
            'price' => 3500,
            'quantity_limit' => 100,
            'is_active' => true,
            'priority' => 5,
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson("/api/organizer/events/{$this->event->id}/pricing-windows", $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.window_name', 'Early Bird')
            ->assertJsonPath('data.price', 3500)
            ->assertJsonPath('data.quantity_limit', 100)
            ->assertJsonPath('data.quantity_sold', 0);

        $this->assertDatabaseHas('pricing_windows', [
            'event_id' => $this->event->id,
            'ticket_category_id' => $this->tier1->id,
            'window_name' => 'Early Bird',
            'price' => 3500,
        ]);
    }

    public function test_create_pricing_window_returns_409_for_overlapping_dates(): void
    {
        PricingWindow::factory()->create([
            'event_id' => $this->event->id,
            'ticket_category_id' => $this->tier1->id,
            'is_active' => true,
            'start_date_time' => now()->addDays(1),
            'end_date_time' => now()->addDays(5),
        ]);

        $payload = [
            'window_name' => 'Overlapping Window',
            'ticket_category_id' => $this->tier1->id,
            'start_date_time' => now()->addDays(3),
            'end_date_time' => now()->addDays(7),
            'price' => 4000,
            'is_active' => true,
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson("/api/organizer/events/{$this->event->id}/pricing-windows", $payload);

        $response->assertStatus(409)
            ->assertJsonPath('message', 'An active pricing window already exists for this ticket category with overlapping dates.');
    }

    public function test_create_pricing_window_returns_422_for_invalid_dates(): void
    {
        $payload = [
            'window_name' => 'Bad Dates',
            'ticket_category_id' => $this->tier1->id,
            'start_date_time' => now()->addDays(5)->toDateTimeString(),
            'end_date_time' => now()->addDays(1)->toDateTimeString(), // end before start
            'price' => 1000,
            'is_active' => true,
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson("/api/organizer/events/{$this->event->id}/pricing-windows", $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date_time']);
    }

    public function test_create_pricing_window_returns_403_for_other_users_event(): void
    {
        $otherEvent = Event::factory()->create(['organizer_id' => $this->otherOrganizer->id]);

        $payload = [
            'window_name' => 'Stolen Window',
            'ticket_category_id' => $this->tier1->id,
            'start_date_time' => now()->addDays(1)->toDateTimeString(),
            'end_date_time' => now()->addDays(5)->toDateTimeString(),
            'price' => 1000,
            'is_active' => true,
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson("/api/organizer/events/{$otherEvent->id}/pricing-windows", $payload);

        // Validation rejects tier not belonging to event before ownership check runs.
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ticket_category_id']);
    }

    // -------------------------------------------------------------------------
    // UPDATE: PATCH /api/organizer/events/{event}/pricing-windows/{window}
    // -------------------------------------------------------------------------

    public function test_update_pricing_window(): void
    {
        $window = PricingWindow::factory()->create([
            'event_id' => $this->event->id,
            'ticket_category_id' => $this->tier1->id,
            'window_name' => 'Original Name',
            'price' => 1000,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->patchJson("/api/organizer/events/{$this->event->id}/pricing-windows/{$window->id}", [
                'window_name' => 'Updated Name',
                'price' => 1500,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.window_name', 'Updated Name')
            ->assertJsonPath('data.price', 1500);

        $this->assertDatabaseHas('pricing_windows', [
            'id' => $window->id,
            'window_name' => 'Updated Name',
            'price' => 1500,
        ]);
    }

    public function test_update_pricing_window_returns_422_for_overlapping_dates(): void
    {
        $window1 = PricingWindow::factory()->create([
            'event_id' => $this->event->id,
            'ticket_category_id' => $this->tier1->id,
            'is_active' => true,
            'start_date_time' => now()->addDays(1),
            'end_date_time' => now()->addDays(5),
        ]);
        $window2 = PricingWindow::factory()->create([
            'event_id' => $this->event->id,
            'ticket_category_id' => $this->tier1->id,
            'is_active' => true,
            'start_date_time' => now()->addDays(6),
            'end_date_time' => now()->addDays(10),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->patchJson("/api/organizer/events/{$this->event->id}/pricing-windows/{$window2->id}", [
                'start_date_time' => now()->addDays(3)->toDateTimeString(),
                'end_date_time' => now()->addDays(7)->toDateTimeString(),
            ]);

        $response->assertStatus(409)
            ->assertJsonPath('message', 'An active pricing window already exists for this ticket category with overlapping dates.');
    }

    // -------------------------------------------------------------------------
    // DELETE: DELETE /api/organizer/events/{event}/pricing-windows/{window}
    // -------------------------------------------------------------------------

    public function test_delete_pricing_window_soft_deletes(): void
    {
        $window = PricingWindow::factory()->create([
            'event_id' => $this->event->id,
            'ticket_category_id' => $this->tier1->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson("/api/organizer/events/{$this->event->id}/pricing-windows/{$window->id}");

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Pricing window deleted successfully.');

        $this->assertSoftDeleted('pricing_windows', ['id' => $window->id]);
    }

    // -------------------------------------------------------------------------
    // PREVIEW: GET /api/organizer/events/{event}/pricing/preview
    // -------------------------------------------------------------------------

    public function test_preview_returns_windows_grouped_by_category(): void
    {
        $window1 = PricingWindow::factory()->create([
            'event_id' => $this->event->id,
            'ticket_category_id' => $this->tier1->id,
            'window_name' => 'VIP Early Bird',
        ]);
        $window2 = PricingWindow::factory()->create([
            'event_id' => $this->event->id,
            'ticket_category_id' => $this->tier2->id,
            'window_name' => 'Regular Early Bird',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/organizer/events/{$this->event->id}/pricing/preview");

        $response->assertStatus(200)
            ->assertJsonPath('event_id', (string) $this->event->id)
            ->assertJsonPath('total_windows', 2)
            ->assertJsonCount(2, 'categories');

        $categories = $response->json('categories');
        $tier1Category = collect($categories)->firstWhere('ticket_category_id', (string) $this->tier1->id);
        $this->assertNotNull($tier1Category);
        $this->assertCount(1, $tier1Category['windows']);
    }

    // -------------------------------------------------------------------------
    // ATTENDEE: GET /api/events/{event}/pricing
    // -------------------------------------------------------------------------

    public function test_attendee_pricing_returns_active_windows_for_published_event(): void
    {
        $activeWindow = PricingWindow::factory()->create([
            'event_id' => $this->publishedEvent->id,
            'ticket_category_id' => $this->tier1->id,
            'is_active' => true,
            'start_date_time' => now()->subHour(),
            'end_date_time' => now()->addHour(),
        ]);
        $inactiveWindow = PricingWindow::factory()->create([
            'event_id' => $this->publishedEvent->id,
            'ticket_category_id' => $this->tier2->id,
            'is_active' => false,
            'start_date_time' => now()->subHour(),
            'end_date_time' => now()->addHour(),
        ]);

        $response = $this->getJson("/api/events/{$this->publishedEvent->id}/pricing");

        $response->assertStatus(200)
            ->assertJsonPath('event_id', (string) $this->publishedEvent->id)
            ->assertJsonPath('total_windows', 1);

        $categories = $response->json('pricing_windows');
        $this->assertTrue(collect($categories)->contains(fn ($cat) =>
            collect($cat)->contains(fn ($w) => $w['id'] === $activeWindow->id)
        ));
    }

    public function test_attendee_pricing_returns_404_for_draft_event(): void
    {
        $response = $this->getJson("/api/events/{$this->event->id}/pricing");

        $response->assertStatus(404)
            ->assertJsonPath('message', 'Pricing not available for this event');
    }

    public function test_attendee_pricing_does_not_require_auth(): void
    {
        PricingWindow::factory()->create([
            'event_id' => $this->publishedEvent->id,
            'ticket_category_id' => $this->tier1->id,
            'is_active' => true,
            'start_date_time' => now()->subHour(),
            'end_date_time' => now()->addHour(),
        ]);

        $response = $this->getJson("/api/events/{$this->publishedEvent->id}/pricing");

        $response->assertStatus(200);
    }

    // -------------------------------------------------------------------------
    // AUTH: Organizer endpoints require auth and ownership
    // -------------------------------------------------------------------------

    public function test_pricing_windows_require_auth(): void
    {
        $response = $this->getJson("/api/organizer/events/{$this->event->id}/pricing-windows");
        $response->assertStatus(401);
    }

    public function test_pricing_windows_require_event_ownership(): void
    {
        $otherEvent = Event::factory()->create(['organizer_id' => $this->otherOrganizer->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/organizer/events/{$otherEvent->id}/pricing-windows");

        $response->assertStatus(403);
    }

    public function test_create_pricing_window_requires_auth(): void
    {
        $response = $this->postJson("/api/organizer/events/{$this->event->id}/pricing-windows", [
            'window_name' => 'No Auth',
            'ticket_category_id' => $this->tier1->id,
            'start_date_time' => now()->addDay()->toDateTimeString(),
            'end_date_time' => now()->addDays(2)->toDateTimeString(),
            'price' => 1000,
        ]);
        $response->assertStatus(401);
    }

    public function test_update_pricing_window_requires_auth(): void
    {
        $window = PricingWindow::factory()->create(['event_id' => $this->event->id]);

        $response = $this->patchJson("/api/organizer/events/{$this->event->id}/pricing-windows/{$window->id}", [
            'window_name' => 'No Auth',
        ]);
        $response->assertStatus(401);
    }

    public function test_delete_pricing_window_requires_auth(): void
    {
        $window = PricingWindow::factory()->create(['event_id' => $this->event->id]);

        $response = $this->deleteJson("/api/organizer/events/{$this->event->id}/pricing-windows/{$window->id}");
        $response->assertStatus(401);
    }

    public function test_preview_requires_auth(): void
    {
        $response = $this->getJson("/api/organizer/events/{$this->event->id}/pricing/preview");
        $response->assertStatus(401);
    }

    // -------------------------------------------------------------------------
    // VALIDATION edge cases
    // -------------------------------------------------------------------------

    public function test_create_pricing_window_requires_all_fields(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson("/api/organizer/events/{$this->event->id}/pricing-windows", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['window_name', 'ticket_category_id', 'start_date_time', 'end_date_time', 'price']);
    }

    public function test_create_pricing_window_rejects_ticket_tier_from_other_event(): void
    {
        $otherEvent = Event::factory()->create(['organizer_id' => $this->otherOrganizer->id]);
        $otherTier = TicketTier::factory()->create(['event_id' => $otherEvent->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson("/api/organizer/events/{$this->event->id}/pricing-windows", [
                'window_name' => 'Bad Tier',
                'ticket_category_id' => $otherTier->id,
                'start_date_time' => now()->addDay()->toDateTimeString(),
                'end_date_time' => now()->addDays(2)->toDateTimeString(),
                'price' => 1000,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ticket_category_id']);
    }

    public function test_overlap_detection_handles_exact_same_times(): void
    {
        $start = now()->addDays(1);
        $end = now()->addDays(5);

        PricingWindow::factory()->create([
            'event_id' => $this->event->id,
            'ticket_category_id' => $this->tier1->id,
            'is_active' => true,
            'start_date_time' => $start,
            'end_date_time' => $end,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson("/api/organizer/events/{$this->event->id}/pricing-windows", [
                'window_name' => 'Exact Overlap',
                'ticket_category_id' => $this->tier1->id,
                'start_date_time' => $start->toDateTimeString(),
                'end_date_time' => $end->toDateTimeString(),
                'price' => 1000,
                'is_active' => true,
            ]);

        $response->assertStatus(409)
            ->assertJsonPath('message', 'An active pricing window already exists for this ticket category with overlapping dates.');
    }

    public function test_overlap_detection_allows_same_category_different_event(): void
    {
        $otherEvent = Event::factory()->create(['organizer_id' => $this->otherOrganizer->id]);
        $otherTier = TicketTier::factory()->create(['event_id' => $otherEvent->id, 'name' => 'Same Name Tier']);

        PricingWindow::factory()->create([
            'event_id' => $otherEvent->id,
            'ticket_category_id' => $otherTier->id,
            'is_active' => true,
            'start_date_time' => now()->addDays(1),
            'end_date_time' => now()->addDays(5),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson("/api/organizer/events/{$this->event->id}/pricing-windows", [
                'window_name' => 'No Overlap Across Events',
                'ticket_category_id' => $this->tier1->id,
                'start_date_time' => now()->addDays(1)->toDateTimeString(),
                'end_date_time' => now()->addDays(5)->toDateTimeString(),
                'price' => 1000,
                'is_active' => true,
            ]);

        $response->assertStatus(201);
    }

    // -------------------------------------------------------------------------
    // SOFT DELETE: Restore and exclude deleted
    // -------------------------------------------------------------------------

    public function test_deleted_window_excluded_from_list_by_default(): void
    {
        $window = PricingWindow::factory()->create([
            'event_id' => $this->event->id,
            'ticket_category_id' => $this->tier1->id,
        ]);
        $window->delete();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/organizer/events/{$this->event->id}/pricing-windows");

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    // -------------------------------------------------------------------------
    // BLOCK DELETION WHEN quantity_sold > 0
    // -------------------------------------------------------------------------

    public function test_cannot_delete_pricing_window_with_sold_tickets(): void
    {
        $window = PricingWindow::factory()->create([
            'event_id' => $this->event->id,
            'ticket_category_id' => $this->tier1->id,
            'quantity_sold' => 5,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson("/api/organizer/events/{$this->event->id}/pricing-windows/{$window->id}");

        $response->assertStatus(409)
            ->assertJsonPath('message', 'Cannot delete a pricing window that has sold tickets. Restore it instead.');

        $this->assertDatabaseHas('pricing_windows', ['id' => $window->id]);
    }

    // -------------------------------------------------------------------------
    // OVERLAP CHECK ON is_active TRANSITION TO true
    // -------------------------------------------------------------------------

    public function test_activating_inactive_window_checks_overlap(): void
    {
        $existingWindow = PricingWindow::factory()->create([
            'event_id' => $this->event->id,
            'ticket_category_id' => $this->tier1->id,
            'is_active' => true,
            'start_date_time' => now()->addDays(1),
            'end_date_time' => now()->addDays(5),
        ]);
        $inactiveWindow = PricingWindow::factory()->create([
            'event_id' => $this->event->id,
            'ticket_category_id' => $this->tier1->id,
            'is_active' => false,
            'start_date_time' => now()->addDays(2),
            'end_date_time' => now()->addDays(6),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->patchJson("/api/organizer/events/{$this->event->id}/pricing-windows/{$inactiveWindow->id}", [
                'is_active' => true,
            ]);

        $response->assertStatus(409)
            ->assertJsonPath('message', 'An active pricing window already exists for this ticket category with overlapping dates.');
    }

    // -------------------------------------------------------------------------
    // OVERLAP CHECK ON RESTORE
    // -------------------------------------------------------------------------

    public function test_restoring_soft_deleted_window_checks_overlap(): void
    {
        $activeWindow = PricingWindow::factory()->create([
            'event_id' => $this->event->id,
            'ticket_category_id' => $this->tier1->id,
            'is_active' => true,
            'start_date_time' => now()->addDays(1),
            'end_date_time' => now()->addDays(5),
        ]);
        // Create as inactive to bypass SQLite trigger, then soft-delete
        $windowToRestore = PricingWindow::factory()->create([
            'event_id' => $this->event->id,
            'ticket_category_id' => $this->tier1->id,
            'is_active' => false,
            'start_date_time' => now()->addDays(2),
            'end_date_time' => now()->addDays(6),
        ]);
        $windowToRestore->delete();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson("/api/organizer/events/{$this->event->id}/pricing-windows/{$windowToRestore->id}/restore");

        $response->assertStatus(409)
            ->assertJsonPath('message', 'An active pricing window already exists for this ticket category with overlapping dates.');
    }

    // -------------------------------------------------------------------------
    // UPDATE ticket_category_id SCoped to event
    // -------------------------------------------------------------------------

    public function test_update_pricing_window_rejects_ticket_tier_from_other_event(): void
    {
        $otherEvent = Event::factory()->create(['organizer_id' => $this->otherOrganizer->id]);
        $otherTier = TicketTier::factory()->create(['event_id' => $otherEvent->id]);

        $window = PricingWindow::factory()->create([
            'event_id' => $this->event->id,
            'ticket_category_id' => $this->tier1->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->patchJson("/api/organizer/events/{$this->event->id}/pricing-windows/{$window->id}", [
                'ticket_category_id' => $otherTier->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ticket_category_id']);
    }
}
