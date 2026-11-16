<?php

namespace Database\Factories\Survey;

use App\Models\Survey\Survey;
use App\Models\Survey\SurveyStart;
use App\Models\UserManagement\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SurveyStart>
 */
class SurveyStartFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'survey_id' => Survey::factory(),
            'user_id' => User::factory(),
            'started_at' => now(),
        ];
    }
}
