<?php

namespace App\Console\Commands;

use App\Enums\PermissionAction;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyReminder;
use App\Models\UserManagement\Menu;
use App\Models\UserManagement\MenuRolePermission;
use App\Models\UserManagement\User;
use App\Notifications\SurveyDeadlineReminder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

/**
 * Daily job (see routes/console.php): reminds employees who have not
 * completed an open survey once it enters a reminder period before its end
 * date (config survey.reminder_days, by default 14 days). Every reminder day
 * is sent once per survey; a survey that opens inside the period is
 * reminded on the first run.
 */
#[Signature('survey:send-deadline-reminders {--dry-run : Only report how many employees would be reminded}')]
#[Description('Remind employees who have not completed an open survey that its end date is near')]
class SendSurveyDeadlineReminders extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
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

            $recipients = $this->recipients($survey);

            if ($this->option('dry-run')) {
                $this->info(sprintf(
                    '%s (H-%d, closes %s): %d employee(s) would be reminded.',
                    $survey->title, $reminderDay, Survey::deadlineText($daysLeft), $recipients->count()
                ));

                continue;
            }

            $sent = 0;

            foreach ($recipients->lazyById(200) as $user) {
                try {
                    $user->notify(new SurveyDeadlineReminder($survey, $daysLeft));
                    $sent++;
                } catch (Throwable $exception) {
                    report($exception);
                }
            }

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

    /**
     * Active users whose role may fill in surveys and who have not completed this one.
     *
     * @return Builder<User>
     */
    protected function recipients(Survey $survey): Builder
    {
        $rolesThatTakeSurveys = MenuRolePermission::query()
            ->where('menu_id', Menu::idForKey('survey-form'))
            ->where('permission_id', PermissionAction::Create->value)
            ->select('role_id');

        return User::query()
            ->active()
            ->whereHas('roles', fn (Builder $query) => $query->whereIn('roles.id', $rolesThatTakeSurveys))
            ->whereDoesntHave('participations', fn (Builder $query) => $query->whereBelongsTo($survey));
    }
}
