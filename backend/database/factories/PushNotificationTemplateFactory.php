<?php

namespace Database\Factories;

use App\Features\PushNotifications\Models\PushNotificationTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class PushNotificationTemplateFactory extends Factory
{
    protected $model = PushNotificationTemplate::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'type' => $this->faker->randomElement(['order_confirmation', 'event_reminder', 'promotional']),
            'title' => $this->faker->sentence(3),
            'body' => $this->faker->sentence(8),
            'variables' => ['name', 'event'],
            'is_active' => true,
            'priority' => $this->faker->numberBetween(0, 10),
            'badge' => 1,
            'sound' => 'default',
            'click_action' => null,
            'collapse_key' => null,
        ];
    }
}
