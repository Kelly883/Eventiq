<?php

namespace Tests\Feature;

use App\Features\Pricing\Models\PricingWindow;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Role;
use App\Models\TicketTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingEndpointsTest extends TestCase
{
    use RefreshDatabase;

    private User $organizerUser;
    private User $otherUser;
    private User $adminUser;
    private User $attendeeUser;
    private Organizer $organizer;
    private Organizer $otherOrganizer;
    private Event $event;
    private Event $draftEvent;
    private TicketTier $tier;
    private TicketTier $tier2;
    private string $token;
    private string $otherToken;
    private string $adminToken;

    protected function setUp(): void
    {
        parent::setUp();

        $organizerRole = Role::firstOrCreate(['name' => 'organizer'], ['description' => 'Organizer', 'isSystemRole' => true]);
        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['description' => 'Administrator', 'isSystemRole' => true]);

        $this->organizerUser = User::factory()->create(['role_id' => $organizerRole->id]);
        $this->organizer = Organizer::factory()->create(['user_id' => $this->organizerUser->id]);
        $this->token = $this->organizerUser->createToken('test-token')->plainTextToken;

        $this->otherUser = User::factory()->create(['role' => 'organizer']);
        $this->otherOrganizer = Organizer::factory()->create(['user_id' => $this->otherUser->id]);
        $this->otherToken = $this->otherUser->createToken('other-token')->plainTextToken;

        $this->adminUser = User::factory()->create(['role_id' => $adminRole->id]);
        $this->adminToken = $this->adminUser->createToken('admin-token')->plainTextToken;

        $this->attendeeUser = User::factory()->create();

        $this->event = Event::factory()->create([
            'organizer_id' => $this->organizer->id,
            'status' => 'live',
            'start_datetime' => now()->addDays(10),
            'end_datetime' => now()->addDays(11),
        ]);

        $this->draftEvent = Event::factory()->create([
            'organizer_id' => $this->organizer->id,
            'status' => 'draft',
            'start_datetime' => now()->addDays(10),
            'end_datetime' => now()->addDays(11),
        ]);

        $this->tier = TicketTier::factory()->create([
            'event_id' => $this->event->id,
            'name' => 'General',
            'price' => 10.00,
            'quantity' => 100,
        ]);

        $this->tier2 = TicketTier::factory()->create([
            'event_id' => $this->event->id,
            'name' => 'VIP',
            'price' => 25.00,
            'quantity' => 50,
        ]);
    }

    // ── Auth & Ownership ───────────────────────────────────────────────

    public function test_organizer_can_create_pricing_window(): void
    {
        $resp = $this->withToken($this->token)
            ->postJson("/api/organizer/events/{$this->event->id}/pricing-windows", [
                'window_name' => 'Early Bird',
                'ticket_category_id' => $this->tier->id,
                'start_date_time' => now()->addDay()->toDateTimeString(),
                'end_date_time' => now()->addDays(5)->toDateTimeString(),
                'price' => 50.00,
                'quantity_limit' => 50,
                'is_active' => true,
                'priority' => 10,
            ]);

        $resp->assertStatus(201);
        $data = $resp->json('data');
        $this->assertEquals('Early Bird', $data['window_name']);
        $this->assertEquals($this->event->id, $data['event_id']);
        $this->assertEquals($this->tier->id, $data['ticket_category_id']);
        $this->assertTrue($data['is_active']);
        $this->assertEquals(10, $data['priority']);
    }

    public function test_unauthenticated_user_cannot_create_window(): void
    {
        $resp = $this->postJson("/api/organizer/events/{$this->event->id}/pricing-windows", [
            'window_name' => 'No Auth',
            'ticket_category_id' => $this->tier->id,
            'start_date_time' => now()->addDays(20)->toDateTimeString(),
            'end_date_time' => now()->addDays(25)->toDateTimeString(),
            'price' => 10.00,
        ]);

        $resp->assertStatus(401);
    }

    public function test_wrong_organizer_cannot_create_window(): void
    {
        $resp = $this->withToken($this->otherToken)
            ->postJson("/api/organizer/events/{$this->event->id}/pricing-windows", [
                'window_name' => 'Not Mine',
                'ticket_category_id' => $this->tier->id,
                'start_date_time' => now()->addDays(20)->toDateTimeString(),
                'end_date_time' => now()->addDays(25)->toDateTimeString(),
                'price' => 10.00,
            ]);

        $resp->assertStatus(403);
    }

    public function test_admin_can_create_window_for_any_event(): void
    {
        $resp = $this->withToken($this->adminToken)
            ->postJson("/api/organizer/events/{$this->event->id}/pricing-windows", [
                'window_name' => 'Admin Window',
                'ticket_category_id' => $this->tier->id,
                'start_date_time' => now()->addDays(20)->toDateTimeString(),
                'end_date_time' => now()->addDays(25)->toDateTimeString(),
                'price' => 10.00,
            ]);

        $resp->assertStatus(201);
    }

    // ── Validation ─────────────────────────────────────────────────────

    public function test_end_date_before_start_returns_422(): void
    {
        $resp = $this->withToken($this->token)
            ->postJson("/api/organizer/events/{$this->event->id}/pricing-windows", [
                'window_name' => 'Bad Dates',
                'ticket_category_id' => $this->tier->id,
                'start_date_time' => now()->addDays(5)->toDateTimeString(),
                'end_date_time' => now()->addDay()->toDateTimeString(),
                'price' => 10.00,
            ]);

        $resp->assertStatus(422);
    }

    public function test_missing_required_fields_returns_422(): void
    {
        $resp = $this->withToken($this->token)
            ->postJson("/api/organizer/events/{$this->event->id}/pricing-windows", [
                'price' => 10.00,
            ]);

        $resp->assertStatus(422);
    }

    public function test_invalid_ticket_category_returns_422(): void
    {
        $resp = $this->withToken($this->token)
            ->postJson("/api/organizer/events/{$this->event->id}/pricing-windows", [
                'window_name' => 'Bad Tier',
                'ticket_category_id' => 99999,
                'start_date_time' => now()->addDays(20)->toDateTimeString(),
                'end_date_time' => now()->addDays(25)->toDateTimeString(),
                'price' => 10.00,
            ]);

        $resp->assertStatus(422);
    }

    // ── Overlap Detection ──────────────────────────────────────────────

    public function test_overlapping_dates_returns_409(): void
    {
        // Create first window
        $this->withToken($this->token)
            ->postJson("/api/organizer/events/{$this->event->id}/pricing-windows", [
                'window_name' => 'First',
                'ticket_category_id' => $this->tier->id,
                'start_date_time' => now()->addDay()->toDateTimeString(),
                'end_date_time' => now()->addDays(5)->toDateTimeString(),
                'price' => 10.00,
                'is_active' => true,
            ])->assertStatus(201);

        // Try to create overlapping window
        $resp = $this->withToken($this->token)
            ->postJson("/api/organizer/events/{$this->event->id}/pricing-windows", [
                'window_name' => 'Overlap',
                'ticket_category_id' => $this->tier->id,
                'start_date_time' => now()->addDays(2)->toDateTimeString(),
                'end_date_time' => now()->addDays(4)->toDateTimeString(),
                'price' => 20.00,
                'is_active' => true,
            ]);

        $resp->assertStatus(409);
    }

    public function test_exact_same_dates_returns_409(): void
    {
        $this->withToken($this->token)
            ->postJson("/api/organizer/events/{$this->event->id}/pricing-windows", [
                'window_name' => 'First',
                'ticket_category_id' => $this->tier->id,
                'start_date_time' => now()->addDay()->toDateTimeString(),
                'end_date_time' => now()->addDays(5)->toDateTimeString(),
                'price' => 10.00,
                'is_active' => true,
            ])->assertStatus(201);

        $resp = $this->withToken($this->token)
            ->postJson("/api/organizer/events/{$this->event->id}/pricing-windows", [
                'window_name' => 'Exact',
                'ticket_category_id' => $this->tier->id,
                'start_date_time' => now()->addDay()->toDateTimeString(),
                'end_date_time' => now()->addDays(5)->toDateTimeString(),
                'price' => 20.00,
                'is_active' => true,
            ]);

        $resp->assertStatus(409);
    }

    public function test_non_overlapping_window_succeeds(): void
    {
        $this->withToken($this->token)
            ->postJson("/api/organizer/events/{$this->event->id}/pricing-windows", [
                'window_name' => 'First',
                'ticket_category_id' => $this->tier->id,
                'start_date_time' => now()->addDay()->toDateTimeString(),
                'end_date_time' => now()->addDays(5)->toDateTimeString(),
                'price' => 10.00,
            ])->assertStatus(201);

        // Non-overlapping window for same tier
        $resp = $this->withToken($this->token)
            ->postJson("/api/organizer/events/{$this->event->id}/pricing-windows", [
                'window_name' => 'Second',
                'ticket_category_id' => $this->tier->id,
                'start_date_time' => now()->addDays(10)->toDateTimeString(),
                'end_date_time' => now()->addDays(15)->toDateTimeString(),
                'price' => 20.00,
            ]);

        $resp->assertStatus(201);
    }

    // ── GET /pricing-windows (list) ────────────────────────────────────

    public function test_list_windows_for_event(): void
    {
        $this->withToken($this->token)
            ->postJson("/api/organizer/events/{$this->event->id}/pricing-windows", [
                'window_name' => 'Early Bird',
                'ticket_category_id' => $this->tier->id,
                'start_date_time' => now()->addDay()->toDateTimeString(),
                'end_date_time' => now()->addDays(5)->toDateTimeString(),
                'price' => 10.00,
            ])->assertStatus(201);

        $resp = $this->withToken($this->token)
            ->getJson("/api/organizer/events/{$this->event->id}/pricing-windows");

        $resp->assertStatus(200);
        $data = $resp->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Early Bird', $data[0]['window_name']);
    }

    public function test_filter_by_ticket_category_id(): void
    {
        // Create window for tier1
        $this->withToken($this->token)
            ->postJson("/api/organizer/events/{$this->event->id}/pricing-windows", [
                'window_name' => 'General Window',
                'ticket_category_id' => $this->tier->id,
                'start_date_time' => now()->addDay()->toDateTimeString(),
                'end_date_time' => now()->addDays(5)->toDateTimeString(),
                'price' => 10.00,
            ])->assertStatus(201);

        // Create window for tier2
        $this->withToken($this->token)
            ->postJson("/api/organizer/events/{$this->event->id}/pricing-windows", [
                'window_name' => 'VIP Window',
                'ticket_category_id' => $this->tier2->id,
                'start_date_time' => now()->addDay()->toDateTimeString(),
                'end_date_time' => now()->addDays(5)->toDateTimeString(),
                'price' => 25.00,
            ])->assertStatus(201);

        // Filter by tier1
        $resp = $this->withToken($this->token)
            ->getJson("/api/organizer/events/{$this->event->id}/pricing-windows?ticket_category_id={$this->tier->id}");

        $resp->assertStatus(200);
        $data = $resp->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('General Window', $data[0]['window_name']);
    }

    public function test_windows_sorted_by_priority_desc(): void
    {
        $this->withToken($this->token)
            ->postJson("/api/organizer/events/{$this->event->id}/pricing-windows", [
                'window_name' => 'Low',
                'ticket_category_id' => $this->tier->id,
                'start_date_time' => now()->addDays(20)->toDateTimeString(),
                'end_date_time' => now()->addDays(25)->toDateTimeString(),
                'price' => 10.00,
                'priority' => 1,
            ])->assertStatus(201);

        $this->withToken($this->token)
            ->postJson("/api/organizer/events/{$this->event->id}/pricing-windows", [
                'window_name' => 'High',
                'ticket_category_id' => $this->tier2->id,
                'start_date_time' => now()->addDay()->toDateTimeString(),
                'end_date_time' => now()->addDays(5)->toDateTimeString(),
                'price' => 25.00,
                'priority' => 10,
            ])->assertStatus(201);

        $resp = $this->withToken($this->token)
            ->getJson("/api/organizer/events/{$this->event->id}/pricing-windows");

        $resp->assertStatus(200);
        $data = $resp->json('data');
        $this->assertEquals('High', $data[0]['window_name']);
        $this->assertEquals('Low', $data[1]['window_name']);
    }

    // ── PATCH /pricing-windows/{id} ────────────────────────────────────

    public function test_update_window(): void
    {
        $createResp = $this->withToken($this->token)
            ->postJson("/api/organizer/events/{$this->event->id}/pricing-windows", [
                'window_name' => 'Original',
                'ticket_category_id' => $this->tier->id,
                'start_date_time' => now()->addDays(30)->toDateTimeString(),
                'end_date_time' => now()->addDays(35)->toDateTimeString(),
                'price' => 10.00,
            ]);

        $windowId = $createResp->json('data.id');

        $resp = $this->withToken($this->token)
            ->patchJson("/api/organizer/events/{$this->event->id}/pricing-windows/{$windowId}", [
                'window_name' => 'Updated',
                'price' => 99.99,
            ]);

        $resp->assertStatus(200);
        $this->assertEquals('Updated', $resp->json('data.window_name'));
        $this->assertEquals(99.99, $resp->json('data.price'));
    }

    public function test_update_with_overlap_returns_409(): void
    {
        // Create two non-overlapping windows for tier2
        $w1 = $this->withToken($this->token)
            ->postJson("/api/organizer/events/{$this->event->id}/pricing-windows", [
                'window_name' => 'W1',
                'ticket_category_id' => $this->tier2->id,
                'start_date_time' => now()->addDays(20)->toDateTimeString(),
                'end_date_time' => now()->addDays(25)->toDateTimeString(),
                'price' => 10.00,
                'is_active' => true,
            ]);
        $this->assertEquals(201, $w1->status(), 'W1 should be created');

        $w2 = $this->withToken($this->token)
            ->postJson("/api/organizer/events/{$this->event->id}/pricing-windows", [
                'window_name' => 'W2',
                'ticket_category_id' => $this->tier2->id,
                'start_date_time' => now()->addDays(40)->toDateTimeString(),
                'end_date_time' => now()->addDays(45)->toDateTimeString(),
                'price' => 10.00,
                'is_active' => true,
            ]);
        $this->assertEquals(201, $w2->status(), 'W2 should be created');

        $w2Id = $w2->json('data.id');

        // Try to move w2 to overlap with w1
        $resp = $this->withToken($this->token)
            ->patchJson("/api/organizer/events/{$this->event->id}/pricing-windows/{$w2Id}", [
                'start_date_time' => now()->addDays(22)->toDateTimeString(),
                'end_date_time' => now()->addDays(27)->toDateTimeString(),
            ]);

        $resp->assertStatus(409);
    }

    public function test_update_dates_after_sales_returns_422(): void
    {
        // Create a window directly with sold tickets
        $window = PricingWindow::create([
            'event_id' => $this->event->id,
            'ticket_category_id' => $this->tier->id,
            'window_name' => 'Has Sales',
            'start_date_time' => now()->addDays(50),
            'end_date_time' => now()->addDays(55),
            'price' => 10.00,
            'quantity_limit' => 10,
            'quantity_sold' => 3,
            'is_active' => true,
            'priority' => 1,
        ]);

        $resp = $this->withToken($this->token)
            ->patchJson("/api/organizer/events/{$this->event->id}/pricing-windows/{$window->id}", [
                'start_date_time' => now()->addDays(60)->toDateTimeString(),
            ]);

        $resp->assertStatus(422);
    }

    // ── DELETE /pricing-windows/{id} ───────────────────────────────────

    public function test_soft_delete_window(): void
    {
        $createResp = $this->withToken($this->token)
            ->postJson("/api/organizer/events/{$this->event->id}/pricing-windows", [
                'window_name' => 'To Delete',
                'ticket_category_id' => $this->tier->id,
                'start_date_time' => now()->addDays(30)->toDateTimeString(),
                'end_date_time' => now()->addDays(35)->toDateTimeString(),
                'price' => 10.00,
            ]);

        $windowId = $createResp->json('data.id');

        $resp = $this->withToken($this->token)
            ->deleteJson("/api/organizer/events/{$this->event->id}/pricing-windows/{$windowId}");

        $resp->assertStatus(200);

        // Verify soft-deleted
        $this->assertSoftDeleted('pricing_windows', ['id' => $windowId]);
        // Verify excluded from default queries
        $this->assertNull(PricingWindow::find($windowId));
    }

    public function test_delete_window_with_sales_returns_409(): void
    {
        $window = PricingWindow::create([
            'event_id' => $this->event->id,
            'ticket_category_id' => $this->tier->id,
            'window_name' => 'Has Sales',
            'start_date_time' => now()->addDays(50),
            'end_date_time' => now()->addDays(55),
            'price' => 10.00,
            'quantity_limit' => 10,
            'quantity_sold' => 5,
            'is_active' => true,
            'priority' => 1,
        ]);

        $resp = $this->withToken($this->token)
            ->deleteJson("/api/organizer/events/{$this->event->id}/pricing-windows/{$window->id}");

        $resp->assertStatus(409);
    }

    // ── GET /pricing/preview ───────────────────────────────────────────

    public function test_preview_returns_grouped_by_category(): void
    {
        $this->withToken($this->token)
            ->postJson("/api/organizer/events/{$this->event->id}/pricing-windows", [
                'window_name' => 'General WB',
                'ticket_category_id' => $this->tier->id,
                'start_date_time' => now()->addDay()->toDateTimeString(),
                'end_date_time' => now()->addDays(5)->toDateTimeString(),
                'price' => 10.00,
            ])->assertStatus(201);

        $this->withToken($this->token)
            ->postJson("/api/organizer/events/{$this->event->id}/pricing-windows", [
                'window_name' => 'VIP WB',
                'ticket_category_id' => $this->tier2->id,
                'start_date_time' => now()->addDay()->toDateTimeString(),
                'end_date_time' => now()->addDays(5)->toDateTimeString(),
                'price' => 25.00,
            ])->assertStatus(201);

        $resp = $this->withToken($this->token)
            ->getJson("/api/organizer/events/{$this->event->id}/pricing/preview");

        $resp->assertStatus(200);
        $this->assertEquals($this->event->id, $resp->json('event_id'));
        $this->assertCount(2, $resp->json('categories'));
        $this->assertGreaterThanOrEqual(2, $resp->json('total_windows'));
    }

    public function test_preview_requires_auth(): void
    {
        $resp = $this->getJson("/api/organizer/events/{$this->event->id}/pricing/preview");
        $resp->assertStatus(401);
    }

    // ── Attendee endpoint ──────────────────────────────────────────────

    public function test_attendee_can_see_pricing_without_auth(): void
    {
        // Create an active window
        PricingWindow::create([
            'event_id' => $this->event->id,
            'ticket_category_id' => $this->tier->id,
            'window_name' => 'Active Public',
            'start_date_time' => now()->subSecond(),
            'end_date_time' => now()->addDays(10),
            'price' => 10.00,
            'quantity_limit' => 50,
            'is_active' => true,
            'priority' => 1,
        ]);

        $resp = $this->getJson("/api/events/{$this->event->id}/pricing");
        $resp->assertStatus(200);
        $this->assertEquals($this->event->id, $resp->json('event_id'));
        $this->assertArrayHasKey('pricing_windows', $resp->json());
        $this->assertGreaterThanOrEqual(1, $resp->json('total_windows'));
    }

    public function test_attendee_sees_only_active_windows(): void
    {
        // Active window
        PricingWindow::create([
            'event_id' => $this->event->id,
            'ticket_category_id' => $this->tier->id,
            'window_name' => 'Active',
            'start_date_time' => now()->subSecond(),
            'end_date_time' => now()->addDays(10),
            'price' => 10.00,
            'quantity_limit' => 50,
            'is_active' => true,
            'priority' => 1,
        ]);

        // Inactive window
        PricingWindow::create([
            'event_id' => $this->event->id,
            'ticket_category_id' => $this->tier->id,
            'window_name' => 'Inactive',
            'start_date_time' => now()->addDays(30),
            'end_date_time' => now()->addDays(35),
            'price' => 5.00,
            'quantity_limit' => 10,
            'is_active' => false,
            'priority' => 1,
        ]);

        $resp = $this->getJson("/api/events/{$this->event->id}/pricing");
        $resp->assertStatus(200);

        $totalWindows = $resp->json('total_windows');
        $this->assertEquals(1, $totalWindows, 'Attendee should only see active windows');
    }

    public function test_unpublished_event_returns_404_for_attendees(): void
    {
        $resp = $this->getJson("/api/events/{$this->draftEvent->id}/pricing");
        $resp->assertStatus(404);
    }

    // ── isActive computation (model method) ────────────────────────────

    public function test_isactive_returns_false_for_inactive_flag(): void
    {
        $window = PricingWindow::create([
            'event_id' => $this->event->id,
            'ticket_category_id' => $this->tier->id,
            'window_name' => 'Inactive Test',
            'start_date_time' => now()->addDays(30),
            'end_date_time' => now()->addDays(35),
            'price' => 5.00,
            'quantity_limit' => 10,
            'is_active' => false,
            'priority' => 1,
        ]);

        $this->assertFalse($window->isActive());
    }

    public function test_isactive_returns_false_for_future_window(): void
    {
        $window = PricingWindow::create([
            'event_id' => $this->event->id,
            'ticket_category_id' => $this->tier->id,
            'window_name' => 'Future Test',
            'start_date_time' => now()->addDays(100),
            'end_date_time' => now()->addDays(105),
            'price' => 5.00,
            'quantity_limit' => 10,
            'is_active' => true,
            'priority' => 1,
        ]);

        $this->assertFalse($window->isActive());
    }

    public function test_isactive_returns_true_for_current_window(): void
    {
        $window = PricingWindow::create([
            'event_id' => $this->event->id,
            'ticket_category_id' => $this->tier->id,
            'window_name' => 'Current Test',
            'start_date_time' => now()->subSecond(),
            'end_date_time' => now()->addDays(10),
            'price' => 5.00,
            'quantity_limit' => 10,
            'is_active' => true,
            'priority' => 1,
        ]);

        $this->assertTrue($window->isActive());
    }

    // ── Rate limiting ──────────────────────────────────────────────────

    public function test_rate_limit_is_enforced(): void
    {
        // Override the limiter so we can test it
        \Illuminate\Support\Facades\RateLimiter::for('organizer-pricing', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(5));

        // Hit the limit
        for ($i = 0; $i < 5; $i++) {
            $resp = $this->withToken($this->token)
                ->getJson("/api/organizer/events/{$this->event->id}/pricing-windows");
            $this->assertEquals(200, $resp->status(), "Request {$i} should succeed");
        }

        // 6th request should be throttled
        $resp = $this->withToken($this->token)
            ->getJson("/api/organizer/events/{$this->event->id}/pricing-windows");
        $this->assertEquals(429, $resp->status(), '6th request should be rate-limited');
    }

    // ── New field: is_currently_active ─────────────────────────────────

    public function test_resource_includes_is_currently_active_field(): void
    {
        // Create an active window starting slightly in the future to pass validation
        $this->withToken($this->token)
            ->postJson("/api/organizer/events/{$this->event->id}/pricing-windows", [
                'window_name' => 'Active Now',
                'ticket_category_id' => $this->tier->id,
                'start_date_time' => now()->addSeconds(30)->toDateTimeString(),
                'end_date_time' => now()->addDays(5)->toDateTimeString(),
                'price' => 10.00,
                'is_active' => true,
            ])->assertStatus(201);

        $resp = $this->withToken($this->token)
            ->getJson("/api/organizer/events/{$this->event->id}/pricing-windows");

        $resp->assertStatus(200);
        $data = $resp->json('data');
        $this->assertArrayHasKey('is_currently_active', $data[0]);
        // Window starts in 30s, so it's not yet active
        // The field exists — that's what we're testing here
    }

    public function test_is_currently_active_false_for_expired_window(): void
    {
        // Create a window that is_active=true but expired
        $window = PricingWindow::create([
            'event_id' => $this->event->id,
            'ticket_category_id' => $this->tier->id,
            'window_name' => 'Expired',
            'start_date_time' => now()->subDays(10),
            'end_date_time' => now()->subDay(),
            'price' => 10.00,
            'quantity_limit' => 10,
            'is_active' => true,
            'priority' => 1,
        ]);

        $resp = $this->withToken($this->token)
            ->getJson("/api/organizer/events/{$this->event->id}/pricing-windows?include_deleted=1");

        $resp->assertStatus(200);
        $data = $resp->json('data');
        $expired = collect($data)->firstWhere('id', $window->id);
        $this->assertNotNull($expired);
        $this->assertTrue($expired['is_active']);           // raw flag is true
        $this->assertFalse($expired['is_currently_active']); // but not currently active
    }

    // ── include_deleted on index ───────────────────────────────────────

    public function test_index_excludes_deleted_windows_by_default(): void
    {
        // Create and soft-delete a window
        $createResp = $this->withToken($this->token)
            ->postJson("/api/organizer/events/{$this->event->id}/pricing-windows", [
                'window_name' => 'To Delete',
                'ticket_category_id' => $this->tier->id,
                'start_date_time' => now()->addDays(30)->toDateTimeString(),
                'end_date_time' => now()->addDays(35)->toDateTimeString(),
                'price' => 10.00,
            ]);
        $windowId = $createResp->json('data.id');

        $this->withToken($this->token)
            ->deleteJson("/api/organizer/events/{$this->event->id}/pricing-windows/{$windowId}")
            ->assertStatus(200);

        // Default list should not include deleted
        $resp = $this->withToken($this->token)
            ->getJson("/api/organizer/events/{$this->event->id}/pricing-windows");
        $ids = collect($resp->json('data'))->pluck('id')->all();
        $this->assertNotContains($windowId, $ids);
    }

    public function test_index_include_deleted_shows_soft_deleted_windows(): void
    {
        // Create and soft-delete a window
        $createResp = $this->withToken($this->token)
            ->postJson("/api/organizer/events/{$this->event->id}/pricing-windows", [
                'window_name' => 'To Delete 2',
                'ticket_category_id' => $this->tier->id,
                'start_date_time' => now()->addDays(30)->toDateTimeString(),
                'end_date_time' => now()->addDays(35)->toDateTimeString(),
                'price' => 10.00,
            ]);
        $windowId = $createResp->json('data.id');

        $this->withToken($this->token)
            ->deleteJson("/api/organizer/events/{$this->event->id}/pricing-windows/{$windowId}")
            ->assertStatus(200);

        // With include_deleted=1, deleted windows should appear
        $resp = $this->withToken($this->token)
            ->getJson("/api/organizer/events/{$this->event->id}/pricing-windows?include_deleted=1");
        $ids = collect($resp->json('data'))->pluck('id')->all();
        $this->assertContains($windowId, $ids);
    }

    // ── Past date validation ───────────────────────────────────────────

    public function test_past_start_date_returns_422(): void
    {
        $resp = $this->withToken($this->token)
            ->postJson("/api/organizer/events/{$this->event->id}/pricing-windows", [
                'window_name' => 'Past Date',
                'ticket_category_id' => $this->tier->id,
                'start_date_time' => now()->subDays(5)->toDateTimeString(),
                'end_date_time' => now()->addDays(5)->toDateTimeString(),
                'price' => 10.00,
            ]);

        $resp->assertStatus(422);
    }

    // ── Improved error message for quantity_limit ───────────────────────

    public function test_quantity_limit_below_sales_shows_count(): void
    {
        $window = PricingWindow::create([
            'event_id' => $this->event->id,
            'ticket_category_id' => $this->tier->id,
            'window_name' => 'Has Sales',
            'start_date_time' => now()->addDays(50),
            'end_date_time' => now()->addDays(55),
            'price' => 10.00,
            'quantity_limit' => 10,
            'quantity_sold' => 3,
            'is_active' => true,
            'priority' => 1,
        ]);

        $resp = $this->withToken($this->token)
            ->patchJson("/api/organizer/events/{$this->event->id}/pricing-windows/{$window->id}", [
                'quantity_limit' => 2,
            ]);

        $resp->assertStatus(422);
        $this->assertStringContainsString('3', $resp->json('message'));
    }
}
