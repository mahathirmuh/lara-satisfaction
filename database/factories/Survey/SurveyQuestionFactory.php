<?php

namespace Database\Factories\Survey;

use App\Enums\QuestionType;
use App\Models\Organization\Department;
use App\Models\Organization\DepartmentSection;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SurveyQuestion>
 */
class SurveyQuestionFactory extends Factory
{
    /**
     * Define the model's default state (a required rating question).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'survey_id' => Survey::factory(),
            'department_id' => Department::factory(),
            'type' => QuestionType::Rating,
            'question' => rtrim(fake()->sentence(8), '.').'?',
            'help_text' => null,
            'is_required' => true,
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }

    /**
     * Indicate that the question rates how critical the service is.
     */
    public function criticality(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => QuestionType::Criticality,
        ]);
    }

    /**
     * Indicate that the question collects free text.
     */
    public function text(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => QuestionType::Text,
            'is_required' => false,
        ]);
    }

    /**
     * Indicate that the question is not tied to a department.
     */
    public function general(): static
    {
        return $this->state(fn (array $attributes): array => [
            'department_id' => null,
            'department_section_id' => null,
        ]);
    }

    /**
     * Assign the question to a section (and its department).
     */
    public function inSection(DepartmentSection $section): static
    {
        return $this->state(fn (array $attributes): array => [
            'department_id' => $section->department_id,
            'department_section_id' => $section->id,
        ]);
    }

    /**
     * Indicate that the question may be skipped.
     */
    public function optional(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_required' => false,
        ]);
    }
}
