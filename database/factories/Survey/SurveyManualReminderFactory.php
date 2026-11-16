<?php

namespace Database\Factories\Survey;

use App\Models\Survey\Survey;
use App\Models\Survey\SurveyManualReminder;
use App\Models\UserManagement\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SurveyManualReminder>
 */
class SurveyManualReminderFactory extends Factory
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
            'sent_by' => User::factory(),
            'audience' => SurveyManualReminder::AUDIENCE_ALL,
            'recipients' => fake()->numberBetween(1, 300),
            'skipped' => 0,
            'sent_at' => now(),
        ];
    }

    /**
     * Sent to people chosen one by one.
     */
    public function custom(int $recipients = 1, int $skipped = 0): static
    {
        return $this->state([
            'audience' => SurveyManualReminder::AUDIENCE_CUSTOM,
            'recipients' => $recipients,
            'skipped' => $skipped,
        ]);
    }
}
