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
 * Note: factories do not expose the raw key. For tests that need the raw
 * key (to build a ****** use ApiKeyService::generate() or
 * construct the model directly via ApiKey::create() with a known hash.
 */
class ApiKeyFactory extends Factory
{
    protected $model = ApiKey::class;

    public function definition(): array
    {
        $prefix = Str::random(8);
        $secret = Str::random(40);

        return [
            'organizer_id' => Organizer::factory(),
            'name' => implode(' ', $this->faker->words(3)),
            'key_prefix' => $prefix,
            'hashed_key' => Hash::make("{$prefix}|{$secret}"),
            'scopes' => ['events:read'],
            'revoked_at' => null,
            'expires_at' => null,
        ];
    }

    /** Mark this key as revoked. */
    public function revoked(): static
    {
        return $this->state(['revoked_at' => now()]);
    }

    /** Mark this key as already expired. */
    public function expired(): static
    {
        return $this->state(['expires_at' => now()->subSecond()]);
    }
}
