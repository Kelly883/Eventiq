<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'description' => $this->faker->sentence(8, true),
            'isSystemRole' => false,
        ];
    }

    public function systemRole(): self
    {
        return $this->state([
            'isSystemRole' => true,
        ]);
    }

    public function admin(): self
    {
        return $this->state([
            'name' => 'admin',
            'description' => 'Full administrative access',
            'isSystemRole' => true,
        ]);
    }
}
