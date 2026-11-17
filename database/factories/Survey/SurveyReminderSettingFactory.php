<?php

namespace Database\Factories\Survey;

use App\Models\Survey\SurveyReminderSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SurveyReminderSetting>
 */
class SurveyReminderSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'automatic' => true,
            'updated_by' => null,
        ];
    }

    /**
     * The automatic reminders turned off.
     */
    public function off(): static
    {
        return $this->state(['automatic' => false]);
    }
}
