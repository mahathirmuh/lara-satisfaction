<?php

namespace Database\Factories\Organization;

use App\Models\Organization\Department;
use App\Models\Organization\DepartmentSection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DepartmentSection>
 */
class DepartmentSectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'name' => Str::title(fake()->unique()->words(2, true)),
            'description' => fake()->sentence(),
            'sort_order' => fake()->numberBetween(1, 20),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the team picture and work summary were uploaded.
     */
    public function withOverview(): static
    {
        return $this->state(fn (array $attributes): array => [
            'team_photo_path' => 'sections/team.webp',
            'team_caption' => 'Team (Left to Right): '.fake()->name().', '.fake()->name(),
            'work_summary_path' => 'sections/work-summary.webp',
            'work_summary_caption' => 'Work Summary Matrix Semester 1',
        ]);
    }

    /**
     * Indicate that the section is no longer assessed.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
