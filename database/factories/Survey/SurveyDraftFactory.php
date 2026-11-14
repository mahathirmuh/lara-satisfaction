<?php

namespace Database\Factories\Survey;

use App\Models\Survey\Survey;
use App\Models\Survey\SurveyDraft;
use App\Models\UserManagement\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SurveyDraft>
 */
class SurveyDraftFactory extends Factory
{
    /**
     * Define the model's default state (an empty draft on the first step).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'survey_id' => Survey::factory(),
            'user_id' => User::factory(),
            'payload' => ['answers' => [], 'evaluations' => [], 'step' => 0],
        ];
    }
}
