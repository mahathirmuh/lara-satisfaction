<?php

namespace Database\Factories\UserManagement;

use App\Models\UserManagement\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->jobTitle().' '.fake()->unique()->numberBetween(1, 99999),
            'home_page' => null,
        ];
    }
}
