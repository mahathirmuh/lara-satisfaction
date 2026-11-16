<?php

namespace App\Console\Commands;

use App\Models\Survey\Survey;
use App\Models\Survey\SurveyReminder;
use App\Services\Survey\SurveyReminders;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Daily job (see routes/console.php): reminds employees who have not
 * completed an open survey once it enters a reminder period before its end
 * date (config survey.reminder_days, by default 14 days). Every reminder day
 * is sent once per survey; a survey that opens inside the period is
 * reminded on the first run. Who is reminded: see SurveyReminders.
 */
#[Signature('survey:send-deadline-reminders {--dry-run : Only report how many employees would be reminded}')]
#[Description('Remind employees who have not completed an open survey that its end date is near')]
class SendSurveyDeadlineReminders extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(SurveyReminders $reminders): int
    {
        $reminderDays = collect(config('survey.reminder_days', []))->sort()->values();

        if ($reminderDays->isEmpty()) {
            $this->info('No reminder days configured (SURVEY_REMINDER_DAYS).');

            return self::SUCCESS;
        }

        foreach (Survey::query()->open()->orderBy('end_date')->get() as $survey) {
            $daysLeft = $survey->daysLeft();
            $reminderDay = $reminderDays->first(fn (int $day): bool => $daysLeft <= $day);

            if ($reminderDay === null || $this->alreadyReminded($survey, $reminderDay)) {
                continue;
            }

            $recipients = $reminders->pendingFor($survey);

            if ($this->option('dry-run')) {
                $this->info(sprintf(
                    '%s (H-%d, closes %s): %d employee(s) would be reminded.',
                    $survey->title, $reminderDay, Survey::deadlineText($daysLeft), $recipients->count()
                ));

                continue;
            }

            $sent = $reminders->remind($survey, $recipients);

            SurveyReminder::create([
                'survey_id' => $survey->id,
                'days_before' => $reminderDay,
                'recipients' => $sent,
                'sent_at' => now(),
            ]);

            $this->info(sprintf('%s (H-%d): reminded %d employee(s).', $survey->title, $reminderDay, $sent));
        }

        return self::SUCCESS;
    }

    /**
     * Whether the reminder of this day was already sent for the survey.
     */
    protected function alreadyReminded(Survey $survey, int $reminderDay): bool
    {
        return SurveyReminder::query()->whereBelongsTo($survey)->where('days_before', $reminderDay)->exists();
    }
}
