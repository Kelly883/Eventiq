<?php

namespace Tests\Feature;

use App\Features\Inventory\Models\InventoryAdjustment;
use App\Features\Inventory\Models\TicketInventory;
use App\Features\Pricing\Models\PricingWindow;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\TicketTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryEndpointTest extends TestCase
{
    use RefreshDatabase;

    private User $organizerUser;
    private Organizer $organizer;
    private User $otherOrganizerUser;
    private Event $event;
    private TicketTier $tier1;
    private TicketTier $tier2;
    private TicketInventory $inventory1;
    private TicketInventory $inventory2;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organizerUser = User::factory()->create(['role' => 'organizer']);
        $this->organizer = Organizer::factory()->create(['user_id' => $this->organizerUser->id]);
        $this->token = $this->organizerUser->createToken('test-token')->plainTextToken;

        $this->otherOrganizerUser = User::factory()->create(['role' => 'organizer']);
        Organizer::factory()->create(['user_id' => $this->otherOrganizerUser->id]);

        $this->event = Event::factory()->create([
            'organizer_id' => $this->organizer->id,
            'title' => 'Inventory Test Event',
            'capacity' => 500,
            'start_datetime' => now()->addDays(10),
            'end_datetime' => now()->addDays(10)->addHours(4),
        ]);

        $this->tier1 = TicketTier::factory()->create([
            'event_id' => $this->event->id,
            'name' => 'VIP',
            'price' => 5000,
            'quantity' => 100,
        ]);
        $this->tier2 = TicketTier::factory()->create([
            'event_id' => $this->event->id,
            'name' => 'Regular',
            'price' => 1000,
            'quantity' => 200,
        ]);

        $this->inventory1 = TicketInventory::factory()->create([
            'event_id' => $this->event->id,
            'ticket_tier_id' => $this->tier1->id,
            'total_allocated' => 100,
            'total_sold' => 30,
            'low_stock_threshold' => 10,
        ]);
        $this->inventory2 = TicketInventory::factory()->create([
            'event_id' => $this->event->id,
            'ticket_tier_id' => $this->tier2->id,
            'total_allocated' => 200,
            'total_sold' => 50,
            'low_stock_threshold' => 10,
        ]);
    }

    private function withOrganizerToken(): self
    {
        return $this->withHeader('Authorization', 'Bearer ' . $this->token);
    }

    // ═══════════════════════════════════════════════════
    // 1. AUTHENTICATION & AUTHORIZATION
    // ═══════════════════════════════════════════════════

    public function test_all_endpoints_reject_without_token(): void
    {
        $endpoints = [
            ['get', "/api/organizer/events/{$this->event->id}/inventory/summary"],
            ['get', "/api/organizer/events/{$this->event->id}/inventory"],
            ['patch', "/api/organizer/events/{$this->event->id}/inventory/adjust"],
            ['get', "/api/organizer/events/{$this->event->id}/inventory/export"],
            ['get', "/api/organizer/events/{$this->event->id}/inventory/audit-log"],
        ];

        foreach ($endpoints as [$method, $url]) {
            $response = $this->$method($url);
            $response->assertStatus(401, "{$method} {$url} should return 401");
        }
    }

    public function test_all_endpoints_reject_other_organizer(): void
    {
        $otherToken = $this->otherOrganizerUser->createToken('other')->plainTextToken;

        $endpoints = [
            ['get', "/api/organizer/events/{$this->event->id}/inventory/summary"],
            ['get', "/api/organizer/events/{$this->event->id}/inventory"],
            ['patch', "/api/organizer/events/{$this->event->id}/inventory/adjust"],
            ['get', "/api/organizer/events/{$this->event->id}/inventory/export"],
            ['get', "/api/organizer/events/{$this->event->id}/inventory/audit-log"],
        ];

        foreach ($endpoints as [$method, $url]) {
            $response = $this->withHeader('Authorization', 'Bearer ' . $otherToken)->$method($url);
            $response->assertStatus(403, "{$method} {$url} should return 403");
        }
    }

    public function test_all_endpoints_return_404_for_nonexistent_event(): void
    {
        $endpoints = [
            ['get', '/api/organizer/events/99999/inventory/summary'],
            ['get', '/api/organizer/events/99999/inventory'],
            ['get', '/api/organizer/events/99999/inventory/export'],
            ['get', '/api/organizer/events/99999/inventory/audit-log'],
        ];

        foreach ($endpoints as [$method, $url]) {
            $response = $this->withOrganizerToken()->$method($url);
            $response->assertStatus(404, "{$method} {$url} should return 404");
        }
    }

    // ═══════════════════════════════════════════════════
    // 2. SUMMARY ENDPOINT
    // ═══════════════════════════════════════════════════

    public function test_summary_returns_correct_totals(): void
    {
        $response = $this->withOrganizerToken()->getJson("/api/organizer/events/{$this->event->id}/inventory/summary");

        $response->assertStatus(200)
            ->assertJsonPath('totalCapacity', 300)
            ->assertJsonPath('totalSold', 80)
            ->assertJsonPath('totalAvailable', 220)
            ->assertJsonPath('utilizationPercentage', 26.67)
            ->assertJsonPath('lowStockTierCount', 0)
            ->assertJsonCount(2, 'tiers');
    }

    public function test_summary_shows_low_stock_count(): void
    {
        $this->inventory1->update(['total_allocated' => 35, 'total_sold' => 30]);

        $response = $this->withOrganizerToken()->getJson("/api/organizer/events/{$this->event->id}/inventory/summary");

        $response->assertStatus(200)
            ->assertJsonPath('lowStockTierCount', 1);
    }

    // ═══════════════════════════════════════════════════
    // 3. INVENTORY DETAIL ENDPOINT
    // ═══════════════════════════════════════════════════

    public function test_inventory_returns_detailed_data_with_pricing_windows(): void
    {
        PricingWindow::factory()->create([
            'event_id' => $this->event->id,
            'ticket_category_id' => $this->tier1->id,
            'window_name' => 'Early Bird',
            'quantity_limit' => 50,
            'quantity_sold' => 10,
        ]);

        $response = $this->withOrganizerToken()->getJson("/api/organizer/events/{$this->event->id}/inventory");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'tiers');

        // Find VIP tier in response (order may vary)
        $vipTier = collect($response->json('tiers'))->firstWhere('tierName', 'VIP');
        $this->assertNotNull($vipTier);
        $this->assertEquals(100, $vipTier['totalAllocated']);
        $this->assertEquals(30, $vipTier['totalSold']);
        $this->assertEquals(70, $vipTier['totalAvailable']);
        $this->assertCount(1, $vipTier['pricingWindows']);
    }

    public function test_inventory_filters_by_tier(): void
    {
        $response = $this->withOrganizerToken()->getJson(
            "/api/organizer/events/{$this->event->id}/inventory?tierFilter={$this->tier1->id}"
        );

        $response->assertStatus(200)
            ->assertJsonCount(1, 'tiers')
            ->assertJsonPath('tiers.0.tierName', 'VIP');
    }

    public function test_inventory_sorting_works(): void
    {
        $response = $this->withOrganizerToken()->getJson(
            "/api/organizer/events/{$this->event->id}/inventory?sortBy=allocated&sortOrder=desc"
        );

        $response->assertStatus(200);
        $tiers = $response->json('tiers');
        $this->assertEquals('Regular', $tiers[0]['tierName']);
        $this->assertEquals('VIP', $tiers[1]['tierName']);
    }

    public function test_inventory_rejects_invalid_tier_filter(): void
    {
        $response = $this->withOrganizerToken()->getJson(
            "/api/organizer/events/{$this->event->id}/inventory?tierFilter=nonexistent-id"
        );

        $response->assertStatus(400);
    }

    // ═══════════════════════════════════════════════════
    // 4. ADJUST ENDPOINT
    // ═══════════════════════════════════════════════════

    public function test_adjust_updates_inventory_and_creates_audit(): void
    {
        $response = $this->withOrganizerToken()->patchJson(
            "/api/organizer/events/{$this->event->id}/inventory/adjust",
            ['tierIdOrWindowId' => (string) $this->tier1->id, 'newQuantity' => 150, 'reason' => 'Restock']
        );

        $response->assertStatus(200)
            ->assertJsonPath('tierId', (string) $this->tier1->id)
            ->assertJsonPath('newQuantity', 150)
            ->assertJsonPath('previousQuantity', 100)
            ->assertJsonPath('quantityDelta', 50);

        $this->assertDatabaseHas('ticket_inventory', [
            'ticket_tier_id' => $this->tier1->id,
            'total_allocated' => 150,
        ]);
        $this->assertDatabaseHas('inventory_adjustments', [
            'ticket_tier_id' => $this->tier1->id,
            'quantity_before' => 100,
            'quantity_after' => 150,
            'reason' => 'Restock',
        ]);
    }

    public function test_adjust_rejects_quantity_exceeding_event_capacity(): void
    {
        $response = $this->withOrganizerToken()->patchJson(
            "/api/organizer/events/{$this->event->id}/inventory/adjust",
            ['tierIdOrWindowId' => (string) $this->tier1->id, 'newQuantity' => 600]
        );

        $response->assertStatus(400)
            ->assertJsonPath('errors.newQuantity.0', 'New quantity cannot exceed event capacity of 500');
    }

    public function test_adjust_rejects_when_sold_exceeds_new_quantity(): void
    {
        $response = $this->withOrganizerToken()->patchJson(
            "/api/organizer/events/{$this->event->id}/inventory/adjust",
            ['tierIdOrWindowId' => (string) $this->tier1->id, 'newQuantity' => 20]
        );

        $response->assertStatus(400)
            ->assertJsonPath('errors.newQuantity.0', 'New quantity cannot be less than already sold (30)');
    }

    public function test_adjust_rejects_negative_quantity(): void
    {
        $response = $this->withOrganizerToken()->patchJson(
            "/api/organizer/events/{$this->event->id}/inventory/adjust",
            ['tierIdOrWindowId' => (string) $this->tier1->id, 'newQuantity' => -5]
        );

        $response->assertStatus(400);
    }

    public function test_adjust_to_zero_works(): void
    {
        $this->inventory1->update(['total_sold' => 0]);

        $response = $this->withOrganizerToken()->patchJson(
            "/api/organizer/events/{$this->event->id}/inventory/adjust",
            ['tierIdOrWindowId' => (string) $this->tier1->id, 'newQuantity' => 0]
        );

        $response->assertStatus(200)
            ->assertJsonPath('newQuantity', 0);
    }

    public function test_adjust_returns_404_for_nonexistent_tier(): void
    {
        $response = $this->withOrganizerToken()->patchJson(
            "/api/organizer/events/{$this->event->id}/inventory/adjust",
            ['tierIdOrWindowId' => 'nonexistent-id', 'newQuantity' => 50]
        );

        $response->assertStatus(404);
    }

    public function test_adjust_works_for_pricing_window(): void
    {
        $window = PricingWindow::factory()->create([
            'event_id' => $this->event->id,
            'ticket_category_id' => $this->tier1->id,
            'window_name' => 'Early Bird',
            'quantity_limit' => 50,
            'quantity_sold' => 5,
        ]);

        $response = $this->withOrganizerToken()->patchJson(
            "/api/organizer/events/{$this->event->id}/inventory/adjust",
            ['tierIdOrWindowId' => (string) $window->id, 'newQuantity' => 75]
        );

        $response->assertStatus(200)
            ->assertJsonPath('newQuantity', 75);
    }

    public function test_adjust_idempotency_key_prevents_duplicate(): void
    {
        $payload = ['tierIdOrWindowId' => (string) $this->tier1->id, 'newQuantity' => 150];

        $first = $this->withOrganizerToken()
            ->withHeader('Idempotency-Key', 'adjust-key-123')
            ->patchJson("/api/organizer/events/{$this->event->id}/inventory/adjust", $payload);
        $first->assertStatus(200);

        $second = $this->withOrganizerToken()
            ->withHeader('Idempotency-Key', 'adjust-key-123')
            ->patchJson("/api/organizer/events/{$this->event->id}/inventory/adjust", $payload);
        $second->assertStatus(200);

        $this->assertEquals($first->json(), $second->json());
        $this->assertEquals(1, InventoryAdjustment::where('ticket_tier_id', $this->tier1->id)->count());
    }

    // ═══════════════════════════════════════════════════
    // 5. EXPORT ENDPOINT
    // ═══════════════════════════════════════════════════

    public function test_export_returns_csv_by_default(): void
    {
        $response = $this->withOrganizerToken()->get("/api/organizer/events/{$this->event->id}/inventory/export");

        $response->assertStatus(200);
        $this->assertStringStartsWith('text/csv', $response->headers->get('Content-Type'));
    }

    public function test_export_returns_json_when_requested(): void
    {
        $response = $this->withOrganizerToken()->get(
            "/api/organizer/events/{$this->event->id}/inventory/export?format=json"
        );

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/json');

        $data = json_decode($response->getContent(), true);
        $this->assertEquals($this->event->id, $data['eventId']);
        $this->assertCount(2, $data['inventory']);
    }

    public function test_export_includes_history_when_requested(): void
    {
        InventoryAdjustment::factory()->create([
            'event_id' => $this->event->id,
            'ticket_tier_id' => $this->tier1->id,
            'organizer_id' => $this->organizerUser->id,
        ]);

        $response = $this->withOrganizerToken()->get(
            "/api/organizer/events/{$this->event->id}/inventory/export?format=json&includeHistory=true"
        );

        $response->assertStatus(200);
        $data = json_decode($response->getContent(), true);
        $this->assertCount(1, $data['history']);
    }

    // ═══════════════════════════════════════════════════
    // 6. AUDIT LOG ENDPOINT
    // ═══════════════════════════════════════════════════

    public function test_audit_log_returns_paginated_results(): void
    {
        InventoryAdjustment::factory()->count(3)->create([
            'event_id' => $this->event->id,
            'ticket_tier_id' => $this->tier1->id,
            'organizer_id' => $this->organizerUser->id,
        ]);

        $response = $this->withOrganizerToken()->getJson(
            "/api/organizer/events/{$this->event->id}/inventory/audit-log"
        );

        $response->assertStatus(200)
            ->assertJsonPath('total', 3)
            ->assertJsonPath('page', 1)
            ->assertJsonPath('limit', 50)
            ->assertJsonCount(3, 'adjustments');
    }

    public function test_audit_log_sorts_most_recent_first(): void
    {
        $old = InventoryAdjustment::factory()->create([
            'event_id' => $this->event->id,
            'ticket_tier_id' => $this->tier1->id,
            'organizer_id' => $this->organizerUser->id,
            'created_at' => now()->subDays(5),
        ]);
        $new = InventoryAdjustment::factory()->create([
            'event_id' => $this->event->id,
            'ticket_tier_id' => $this->tier1->id,
            'organizer_id' => $this->organizerUser->id,
            'created_at' => now(),
        ]);

        $response = $this->withOrganizerToken()->getJson(
            "/api/organizer/events/{$this->event->id}/inventory/audit-log"
        );

        $response->assertStatus(200);
        $adjustments = $response->json('adjustments');
        $this->assertEquals($new->id, $adjustments[0]['id']);
        $this->assertEquals($old->id, $adjustments[1]['id']);
    }

    public function test_audit_log_filters_by_tier(): void
    {
        InventoryAdjustment::factory()->create([
            'event_id' => $this->event->id,
            'ticket_tier_id' => $this->tier1->id,
            'organizer_id' => $this->organizerUser->id,
        ]);
        InventoryAdjustment::factory()->create([
            'event_id' => $this->event->id,
            'ticket_tier_id' => $this->tier2->id,
            'organizer_id' => $this->organizerUser->id,
        ]);

        $response = $this->withOrganizerToken()->getJson(
            "/api/organizer/events/{$this->event->id}/inventory/audit-log?tierFilter={$this->tier1->id}"
        );

        $response->assertStatus(200)
            ->assertJsonPath('total', 1)
            ->assertJsonCount(1, 'adjustments');
    }

    public function test_audit_log_filters_by_type(): void
    {
        InventoryAdjustment::factory()->create([
            'event_id' => $this->event->id,
            'ticket_tier_id' => $this->tier1->id,
            'organizer_id' => $this->organizerUser->id,
            'adjustment_type' => 'manual_increase',
        ]);
        InventoryAdjustment::factory()->create([
            'event_id' => $this->event->id,
            'ticket_tier_id' => $this->tier1->id,
            'organizer_id' => $this->organizerUser->id,
            'adjustment_type' => 'manual_decrease',
        ]);

        $response = $this->withOrganizerToken()->getJson(
            "/api/organizer/events/{$this->event->id}/inventory/audit-log?adjustmentType=manual_increase"
        );

        $response->assertStatus(200)
            ->assertJsonPath('total', 1);
    }

    // ═══════════════════════════════════════════════════
    // 7. EDGE CASES
    // ═══════════════════════════════════════════════════

    public function test_adjust_to_deleted_tier_returns_404(): void
    {
        $this->tier1->delete();

        $response = $this->withOrganizerToken()->patchJson(
            "/api/organizer/events/{$this->event->id}/inventory/adjust",
            ['tierIdOrWindowId' => (string) $this->tier1->id, 'newQuantity' => 50]
        );

        $response->assertStatus(404);
    }

    public function test_adjust_total_capacity_check_across_tiers(): void
    {
        $response = $this->withOrganizerToken()->patchJson(
            "/api/organizer/events/{$this->event->id}/inventory/adjust",
            ['tierIdOrWindowId' => (string) $this->tier1->id, 'newQuantity' => 400]
        );

        $response->assertStatus(400)
            ->assertJsonPath('message', 'Total inventory cannot exceed event capacity');
    }

    public function test_summary_with_no_inventory(): void
    {
        TicketInventory::query()->delete();

        $response = $this->withOrganizerToken()->getJson("/api/organizer/events/{$this->event->id}/inventory/summary");

        $response->assertStatus(200)
            ->assertJsonPath('totalCapacity', 0)
            ->assertJsonPath('totalSold', 0)
            ->assertJsonPath('totalAvailable', 0)
            ->assertJsonPath('lowStockTierCount', 0);
    }
}
