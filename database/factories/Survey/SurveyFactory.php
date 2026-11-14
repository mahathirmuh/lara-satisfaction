<?php

namespace Database\Factories\Survey;

use App\Enums\SurveyStatus;
use App\Models\Survey\Survey;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Survey>
 */
class SurveyFactory extends Factory
{
    /**
     * Define the model's default state (an open survey).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => 'Operational Audit '.fake()->unique()->numberBetween(2000, 9999),
            'description' => fake()->paragraph(),
            'start_date' => today()->subDays(7),
            'end_date' => today()->addDays(30),
            'status' => SurveyStatus::Active,
            'estimated_minutes' => 15,
        ];
    }

    /**
     * Indicate that the survey is not published yet.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SurveyStatus::Draft,
        ]);
    }

    /**
     * Indicate that the survey was closed.
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SurveyStatus::Closed,
        ]);
    }

    /**
     * Indicate that the survey's period has already ended.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'start_date' => today()->subDays(60),
            'end_date' => today()->subDay(),
        ]);
    }

    /**
     * Indicate that the survey's period has not started yet.
     */
    public function upcoming(): static
    {
        return $this->state(fn (array $attributes): array => [
            'start_date' => today()->addDay(),
            'end_date' => today()->addDays(30),
        ]);
    }
}
