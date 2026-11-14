<?php

namespace Database\Factories\Organization;

use App\Models\Organization\Department;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => Str::upper(fake()->unique()->lexify('DEP???')),
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->sentence(),
            'icon' => 'building',
            'color' => fake()->randomElement(Department::COLORS),
            'sort_order' => fake()->numberBetween(1, 50),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the department is no longer assessed.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
