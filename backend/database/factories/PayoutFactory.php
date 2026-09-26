<?php

namespace Database\Factories;

use App\Features\Payouts\Models\Payout;
use App\Models\Organizer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payout>
 */
class PayoutFactory extends Factory
{
    protected $model = Payout::class;

    public function definition(): array
    {
        return [
            'id'                           => (string) Str::uuid(),
            'organizer_id'                 => Organizer::factory(),
            'settlement_period_start_date' => now()->subDays(14),
            'settlement_period_end_date'   => now()->subDays(7),
            'gross_revenue'                => $this->faker->randomFloat(2, 1000, 10000),
            'refunds_deducted'             => $this->faker->randomFloat(2, 0, 500),
            'net_revenue'                  => $this->faker->randomFloat(2, 500, 9500),
            'platform_commission_percentage' => 10.00,
            'platform_commission_amount'      => $this->faker->randomFloat(2, 50, 1000),
            'processing_fee_percentage'       => 2.50,
            'processing_fee_amount'           => $this->faker->randomFloat(2, 10, 250),
            'tax_withholding_percentage'      => 0.00,
            'tax_withholding_amount'          => 0.00,
            'payout_amount'                   => $this->faker->randomFloat(2, 100, 9000),
            'currency'                        => 'USD',
            'payout_method'                   => $this->faker->randomElement(['bank_transfer', 'paystack', 'flutterwave']),
            'status'                          => Payout::STATUS_COMPLETED,
            'completed_at'                    => now(),
        ];
    }

    public function pending(): self
    {
        return $this->state(['status' => Payout::STATUS_PENDING, 'completed_at' => null]);
    }

    public function processing(): self
    {
        return $this->state(['status' => Payout::STATUS_PROCESSING, 'completed_at' => null]);
    }

    public function approved(): self
    {
        return $this->state(['status' => Payout::STATUS_APPROVED, 'completed_at' => null]);
    }

    public function failed(): self
    {
        return $this->state(['status' => Payout::STATUS_FAILED, 'completed_at' => null]);
    }
}
