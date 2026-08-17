<?php

namespace Database\Factories;

use App\Models\AnalyticsSalesTimeline;
use App\Models\Event;
use App\Models\TicketTier;
use App\Features\Pricing\Models\PricingWindow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnalyticsSalesTimeline>
 */
class AnalyticsSalesTimelineFactory extends Factory
{
    protected $model = AnalyticsSalesTimeline::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'ticket_tier_id' => TicketTier::factory(),
            'pricing_window_id' => PricingWindow::factory(),
            'sale_timestamp' => now()->subDays(rand(1, 30)),
            'quantity' => $this->faker->numberBetween(1, 10),
            'unit_price' => $this->faker->randomFloat(2, 10, 200),
            'total_amount' => $this->faker->randomFloat(2, 50, 2000),
            'buyer_email' => $this->faker->safeEmail(),
            'source' => $this->faker->randomElement(['web', 'mobile', 'pos']),
        ];
    }
}
