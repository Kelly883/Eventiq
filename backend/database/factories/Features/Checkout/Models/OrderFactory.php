<?php

namespace Database\Factories\Features\Checkout\Models;

use App\Models\Event;
use App\Features\Checkout\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'user_id' => null,
            'status' => 'pending',
            'total_amount' => 0,
            'currency' => 'NGN',
            'payment_gateway' => null,
            'payment_intent_id' => null,
            'gateway_transaction_id' => null,
        ];
    }
}
