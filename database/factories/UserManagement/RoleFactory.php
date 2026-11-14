<?php

namespace Database\Factories\UserManagement;

use App\Enums\RoleScope;
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
            'func_role' => RoleScope::All,
            'setting_role' => null,
            'home_page' => null,
        ];
    }

    /**
     * Limit the role's data scope to the given companies.
     *
     * @param  array<int, int>  $companyIds
     */
    public function forCompanies(array $companyIds): static
    {
        return $this->state(fn (array $attributes): array => [
            'func_role' => RoleScope::Company,
            'setting_role' => array_values($companyIds),
        ]);
    }
}
