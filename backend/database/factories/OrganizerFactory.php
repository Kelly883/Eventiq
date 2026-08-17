<?php

namespace Database\Factories;

use App\Models\Organizer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organizer>
 */
class OrganizerFactory extends Factory
{
    protected $model = Organizer::class;

    private function randomHex(): string
    {
        return '#' . strtoupper(str_pad(dechex(random_int(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT));
    }

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'userId' => fn (array $org) => $org['user_id'],
            'displayName' => $this->faker->company(),
            'bio' => $this->faker->paragraph(),
            'avatarUrl' => $this->faker->imageUrl(200, 200, 'business', true),
            'email' => $this->faker->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'website' => $this->faker->url(),
            'socialLinks' => [
                'twitter' => $this->faker->url(),
                'instagram' => $this->faker->url(),
                'linkedin' => $this->faker->url(),
                'youtube' => null,
            ],
            'brandingColors' => [
                'primaryColor' => $this->randomHex(),
                'accentColor' => $this->randomHex(),
            ],
            'timezone' => $this->faker->timezone(),
            'currency' => $this->faker->randomElement(['NGN', 'USD', 'GBP', 'EUR']),
            'country' => $this->faker->countryCode(),
            'verificationStatus' => 'verified',
            'paymentDefault' => 'paystack',
            'commissionRate' => $this->faker->randomFloat(2, 0, 15),
            'isPublic' => true,
            'emailPublic' => false,
            'phonePublic' => false,
            'hideSocialLinks' => false,
            'hideBrandingColors' => false,
            'notificationPreferences' => [
                'ticketSales' => true,
                'eventReminders' => true,
                'platformUpdates' => false,
            ],
            'totalEventsCreated' => 0,
            'totalTicketsSold' => 0,
        ];
    }
}
