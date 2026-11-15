<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = \App\Features\Checkout\Models\Order::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement(['pending', 'completed', 'failed', 'refunded']);

        return [
            'user_id' => User::factory(),
            'event_id' => Event::factory(),
            'total_amount' => $this->faker->randomFloat(2, 100, 10000),
            'currency' => 'NGN',
            'status' => $status,
            'payment_gateway' => $this->faker->randomElement(['paystack', 'flutterwave']),
            'payment_intent_id' => 'pi_' . $this->faker->unique()->uuid(),
            'gateway_transaction_id' => 'tx_' . $this->faker->uuid(),
            'device_id' => $this->faker->uuid(),
            'ip_address' => $this->faker->ipv4(),
            'subtotal' => $this->faker->randomFloat(2, 100, 10000),
            'tax_amount' => $this->faker->randomFloat(2, 0, 500),
            'discount_amount' => $this->faker->randomFloat(2, 0, 1000),
            'coupon_code' => $this->faker->optional()->bothify('COUPON-###'),
            'billing_name' => $this->faker->name(),
            'billing_email' => $this->faker->safeEmail(),
            'billing_phone' => $this->faker->phoneNumber(),
            'failure_reason' => $status === 'failed' ? $this->faker->sentence() : null,
            'completed_at' => $status === 'completed' ? now() : null,
            'refunded_at' => $status === 'refunded' ? now() : null,
        ];
    }
}
