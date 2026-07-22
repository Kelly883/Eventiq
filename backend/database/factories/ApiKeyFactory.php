<?php

namespace Database\Factories;

use App\Models\ApiKey;
use App\Models\Organizer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<ApiKey>
 *
 * Usage in tests: generate the raw key before calling factory(), then pass
 * key_prefix and hashed_key as overrides so the test retains the raw key for
 * use in Authorization headers.
 *
 * Example:
 *   $raw = 'ek_test_abc|' . Str::random(32);
 *   $key = ApiKey::factory()->for($organizer)->create([
 *       'key_prefix'  => 'ek_test_abc',
 *       'hashed_key'  => Hash::make($raw),
 *       'scopes'      => ['events:read'],
 *   ]);
 */
class ApiKeyFactory extends Factory
{
    protected $model = ApiKey::class;

    public function definition(): array
    {
        $prefix = 'ek_test_' . Str::random(8);
        $secret = Str::random(32);
        $rawKey = "{$prefix}|{$secret}";

        return [
            'organizer_id' => Organizer::factory(),
            'name' => $this->faker->words(3, true),
            'key_prefix' => $prefix,
            'hashed_key' => Hash::make($rawKey),
            'scopes' => ['events:read', 'orders:read', 'tickets:read'],
            'revoked_at' => null,
            'expires_at' => null,
        ];
    }

    public function revoked(): static
    {
        return $this->state(fn () => ['revoked_at' => now()->subMinute()]);
    }

    public function expired(): static
    {
        return $this->state(fn () => ['expires_at' => now()->subDay()]);
    }
}
