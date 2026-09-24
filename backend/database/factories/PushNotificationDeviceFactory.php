<?php

namespace Database\Factories;

use App\Features\PushNotifications\Models\PushNotificationDevice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PushNotificationDeviceFactory extends Factory
{
    protected $model = PushNotificationDevice::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'token' => $this->faker->uuid(),
            'provider' => $this->faker->randomElement(['fcm', 'apns']),
            'device_type' => $this->faker->randomElement(['web', 'ios', 'android']),
            'offline_enabled' => false,
            'device_name' => $this->faker->word(),
            'model' => $this->faker->word(),
            'app_version' => '1.0.0',
            'os_version' => '14.0',
            'locale' => 'en',
            'timezone' => 'UTC',
        ];
    }
}
