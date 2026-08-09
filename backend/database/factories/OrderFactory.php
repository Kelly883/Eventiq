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
        return [
            'user_id' => User::factory(),
            'event_id' => Event::factory(),
            'total_amount' => $this->faker->randomFloat(2, 100, 10000),
            'currency' => 'NGN',
            'status' => $this->faker->randomElement(['pending', 'completed', 'failed', 'refunded']),
            'payment_gateway' => $this->faker->randomElement(['paystack', 'flutterwave']),
            'payment_intent_id' => 'pi_' . $this->faker->unique()->uuid(),
            'gateway_transaction_id' => 'tx_' . $this->faker->uuid(),
            'device_id' => $this->faker->uuid(),
            'ip_address' => $this->faker->ipv4(),
        ];
    }
}
