<?php

namespace Database\Factories;

use App\Features\Checkout\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = \App\Features\Checkout\Models\Payment::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'payment_intent_id' => 'pi_' . $this->faker->unique()->uuid(),
            'gateway_transaction_id' => 'tx_' . $this->faker->uuid(),
            'amount' => $this->faker->randomFloat(2, 100, 10000),
            'currency' => 'NGN',
            'status' => $this->faker->randomElement(['pending', 'success', 'failed']),
            'gateway' => $this->faker->randomElement(['paystack', 'flutterwave']),
            'idempotency_key' => $this->faker->uuid(),
            'gateway_response' => '{}',
            'fees' => $this->faker->randomFloat(2, 10, 500),
            'net_amount' => $this->faker->randomFloat(2, 100, 10000),
        ];
    }
}
