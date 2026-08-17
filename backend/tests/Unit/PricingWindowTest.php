<?php

namespace Tests\Unit;

use App\Features\Pricing\Models\PricingWindow;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PricingWindowTest extends TestCase
{
    use RefreshDatabase;

    private function makeWindow(array $overrides = []): PricingWindow
    {
        return PricingWindow::factory()->create($overrides);
    }

    // -------------------------------------------------------------------------
    // isActive()
    // -------------------------------------------------------------------------

    public function test_is_active_when_within_window_and_enabled(): void
    {
        $window = $this->makeWindow([
            'is_active' => true,
            'start_date_time' => now()->subHour(),
            'end_date_time' => now()->addHour(),
        ]);

        $this->assertTrue($window->isActive());
    }

    public function test_is_inactive_when_before_start(): void
    {
        $window = $this->makeWindow([
            'is_active' => true,
            'start_date_time' => now()->addHour(),
            'end_date_time' => now()->addHours(3),
        ]);

        $this->assertFalse($window->isActive());
    }

    public function test_is_inactive_when_after_end(): void
    {
        $window = $this->makeWindow([
            'is_active' => true,
            'start_date_time' => now()->subHours(3),
            'end_date_time' => now()->subHour(),
        ]);

        $this->assertFalse($window->isActive());
    }

    public function test_is_inactive_when_disabled_even_if_within_window(): void
    {
        $window = $this->makeWindow([
            'is_active' => false,
            'start_date_time' => now()->subHour(),
            'end_date_time' => now()->addHour(),
        ]);

        $this->assertFalse($window->isActive());
    }

    public function test_is_active_at_exact_start_boundary(): void
    {
        $start = now()->subMinutes(30);
        $window = $this->makeWindow([
            'is_active' => true,
            'start_date_time' => $start,
            'end_date_time' => now()->addHour(),
        ]);

        $this->assertTrue($window->isActive($start));
    }

    public function test_is_active_at_exact_end_boundary(): void
    {
        $end = now()->addMinutes(30);
        $window = $this->makeWindow([
            'is_active' => true,
            'start_date_time' => now()->subHour(),
            'end_date_time' => $end,
        ]);

        $this->assertTrue($window->isActive($end));
    }

    public function test_is_active_with_custom_now(): void
    {
        $customNow = Carbon::create(2026, 1, 1, 12, 0, 0);
        $window = $this->makeWindow([
            'is_active' => true,
            'start_date_time' => $customNow->copy()->subHour(),
            'end_date_time' => $customNow->copy()->addHour(),
        ]);

        $this->assertTrue($window->isActive($customNow));
    }

    // -------------------------------------------------------------------------
    // scopeActive()
    // -------------------------------------------------------------------------

    public function test_scope_active_returns_only_current_active_windows(): void
    {
        $event = Event::factory()->create();

        $activeWindow = PricingWindow::factory()->create([
            'event_id' => $event->id,
            'is_active' => true,
            'start_date_time' => now()->subHour(),
            'end_date_time' => now()->addHour(),
        ]);

        $futureWindow = PricingWindow::factory()->create([
            'event_id' => $event->id,
            'is_active' => true,
            'start_date_time' => now()->addHour(),
            'end_date_time' => now()->addHours(3),
        ]);

        $inactiveWindow = PricingWindow::factory()->create([
            'event_id' => $event->id,
            'is_active' => false,
            'start_date_time' => now()->subHour(),
            'end_date_time' => now()->addHour(),
        ]);

        $results = PricingWindow::active()->get();

        $this->assertTrue($results->contains($activeWindow));
        $this->assertFalse($results->contains($futureWindow));
        $this->assertFalse($results->contains($inactiveWindow));
    }

    // -------------------------------------------------------------------------
    // hasAvailability() and getAvailableQuantityAttribute()
    // -------------------------------------------------------------------------

    public function test_has_availability_when_no_quantity_limit(): void
    {
        $window = $this->makeWindow([
            'quantity_limit' => null,
            'quantity_sold' => 0,
        ]);

        $this->assertTrue($window->hasAvailability());
        $this->assertNull($window->available_quantity);
    }

    public function test_has_availability_when_under_limit(): void
    {
        $window = $this->makeWindow([
            'quantity_limit' => 100,
            'quantity_sold' => 30,
        ]);

        $this->assertTrue($window->hasAvailability());
        $this->assertSame(70, $window->available_quantity);
    }

    public function test_has_no_availability_when_sold_out(): void
    {
        $window = $this->makeWindow([
            'quantity_limit' => 100,
            'quantity_sold' => 100,
        ]);

        $this->assertFalse($window->hasAvailability());
        $this->assertSame(0, $window->available_quantity);
    }

    public function test_available_quantity_never_goes_negative(): void
    {
        $window = $this->makeWindow([
            'quantity_limit' => 50,
            'quantity_sold' => 75,
        ]);

        $this->assertFalse($window->hasAvailability());
        $this->assertSame(0, $window->available_quantity);
    }

    // -------------------------------------------------------------------------
    // incrementSold()
    // -------------------------------------------------------------------------

    public function test_increment_sold_returns_true_when_under_limit(): void
    {
        $window = $this->makeWindow([
            'quantity_limit' => 10,
            'quantity_sold' => 3,
        ]);

        $result = $window->incrementSold(2);

        $this->assertTrue($result);
        $this->assertSame(5, $window->fresh()->quantity_sold);
    }

    public function test_increment_sold_returns_false_when_at_limit(): void
    {
        $window = $this->makeWindow([
            'quantity_limit' => 10,
            'quantity_sold' => 10,
        ]);

        $result = $window->incrementSold(1);

        $this->assertFalse($result);
        $this->assertSame(10, $window->fresh()->quantity_sold);
    }

    public function test_increment_sold_returns_false_when_over_limit(): void
    {
        $window = $this->makeWindow([
            'quantity_limit' => 10,
            'quantity_sold' => 12,
        ]);

        $result = $window->incrementSold(1);

        $this->assertFalse($result);
        $this->assertSame(12, $window->fresh()->quantity_sold);
    }

    public function test_increment_sold_allows_unlimited_when_no_quantity_limit(): void
    {
        $window = $this->makeWindow([
            'quantity_limit' => null,
            'quantity_sold' => 0,
        ]);

        $result = $window->incrementSold(5);

        $this->assertTrue($result);
        $this->assertSame(5, $window->fresh()->quantity_sold);
    }

    // -------------------------------------------------------------------------
    // Model-level validation
    // -------------------------------------------------------------------------

    public function test_creating_window_with_negative_quantity_sold_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        PricingWindow::factory()->create([
            'quantity_sold' => -1,
        ]);
    }

    public function test_creating_window_with_negative_price_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        PricingWindow::factory()->create([
            'price' => -10,
        ]);
    }

    public function test_creating_window_with_negative_priority_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        PricingWindow::factory()->create([
            'priority' => -1,
        ]);
    }
}
