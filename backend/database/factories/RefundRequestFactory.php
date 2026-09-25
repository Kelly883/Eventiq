<?php

namespace Database\Factories;

use App\Features\Refunds\Enums\RefundMethodEnum;
use App\Features\Refunds\Enums\RefundReasonEnum;
use App\Features\Refunds\Models\RefundRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class RefundRequestFactory extends Factory
{
    protected $model = RefundRequest::class;

    public function definition(): array
    {
        return [
            'ticket_id' => $this->faker->randomNumber(),
            'user_id' => User::factory(),
            'event_id' => $this->faker->randomNumber(),
            'original_amount' => $this->faker->randomFloat(2, 10, 500),
            'refund_amount' => $this->faker->randomFloat(2, 10, 500),
            'refund_percentage' => $this->faker->randomFloat(2, 50, 100),
            'reason' => $this->faker->randomElement(RefundReasonEnum::cases())->value,
            'explanation' => $this->faker->paragraph(),
            'refund_method' => $this->faker->randomElement(RefundMethodEnum::cases())->value,
            'status' => 'pending',
            'reference_number' => 'REF-' . strtoupper(Str::random(10)),
            'expected_processing_days' => 3,
        ];
    }
}
