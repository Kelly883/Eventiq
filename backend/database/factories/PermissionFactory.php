<?php

namespace Database\Factories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Permission>
 */
class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'description' => $this->faker->sentence(8, true),
            'group' => $this->faker->word(),
            'category' => $this->faker->word(),
            'riskLevel' => $this->faker->randomElement(['low', 'medium', 'high']),
        ];
    }
}
