<?php

use App\Console\Commands\SendSurveyDeadlineReminders;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
| Requires the scheduler: "* * * * * php artisan schedule:run".
|  - import of the employees of the assessed departments from the directory (before the reminders)
|  - deadline reminders (in-app + email) for open surveys, see config survey.reminder_days;
|    they can be turned off in Survey Management > Reminders
|  - removal of survey drafts that can no longer be submitted (SurveyDraft::prunable())
*/
Schedule::command('users:sync-directory')->dailyAt('06:00')->withoutOverlapping();
Schedule::command('survey:send-deadline-reminders')->dailyAt(SendSurveyDeadlineReminders::RUNS_AT)->withoutOverlapping();
Schedule::command('model:prune')->daily();
