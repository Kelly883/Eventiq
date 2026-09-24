<?php

namespace Database\Factories;

use App\Features\Refunds\Models\RefundPolicy;
use App\Models\Event;
use App\Models\Organizer;
use Illuminate\Database\Eloquent\Factories\Factory;

class RefundPolicyFactory extends Factory
{
    protected $model = RefundPolicy::class;

    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'event_id' => Event::factory(),
            'organizer_id' => Organizer::factory(),
            'refund_window_days' => 14,
            'refund_percentage_before_event' => 100.00,
            'refund_percentage_after_event_start' => 50.00,
            'allow_refunds_after_event_start' => false,
            'processing_time_business_days' => 3,
            'allowed_refund_methods' => ['original_payment', 'store_credit'],
            'requires_approval' => true,
            'auto_approve_threshold' => null,
            'max_refunds_per_user' => null,
            'refund_reasons' => ['event_cancelled', 'change_of_plans'],
            'cancellation_policy' => 'Refunds allowed up to 14 days before event',
            'is_active' => true,
        ];
    }
}
