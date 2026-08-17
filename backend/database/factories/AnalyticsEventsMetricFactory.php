<?php

namespace Database\Factories;

use App\Models\AnalyticsEventsMetric;
use App\Models\Event;
use App\Models\Organizer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnalyticsEventsMetric>
 */
class AnalyticsEventsMetricFactory extends Factory
{
    protected $model = AnalyticsEventsMetric::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'organizer_id' => Organizer::factory(),
            'total_revenue' => $this->faker->randomFloat(2, 0, 50000),
            'total_tickets_sold' => $this->faker->numberBetween(0, 5000),
            'total_page_views' => $this->faker->numberBetween(0, 20000),
            'total_ticket_page_views' => $this->faker->numberBetween(0, 10000),
            'conversion_rate' => $this->faker->randomFloat(2, 0, 15),
            'average_ticket_price' => $this->faker->randomFloat(2, 10, 200),
            'peak_sales_hour' => $this->faker->optional()->numberBetween(0, 23),
            'top_ticket_tier_id' => null,
            'last_updated_at' => now(),
        ];
    }
}
