<?php

namespace App\Services\Survey;

use App\Enums\PermissionAction;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyManualReminder;
use App\Models\UserManagement\Menu;
use App\Models\UserManagement\MenuRolePermission;
use App\Models\UserManagement\User;
use App\Notifications\SurveyDeadlineReminder;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

/**
 * Who the survey respondents are, and the reminding: the active employees of
 * the assessed departments (department_id, imported daily from the directory)
 * whose role may fill in surveys (Create on My Survey). The same people are
 * reminded, listed in Survey Progress and counted in the participation rate.
 * Used by the daily scheduled reminders and by the reminders sent by hand
 * from Survey Management > Reminders.
 */
class SurveyReminders
{
    /**
     * The respondents: active employees of an assessed department whose role
     * may fill in surveys.
     *
     * @return Builder<User>
     */
    public function surveyTakers(): Builder
    {
        $rolesThatTakeSurveys = MenuRolePermission::query()
            ->where('menu_id', Menu::idForKey('survey-form'))
            ->where('permission_id', PermissionAction::Create->value)
            ->select('role_id');

        return User::query()
            ->active()
            ->whereNotNull('department_id')
            ->whereHas('roles', fn (Builder $query) => $query->whereIn('roles.id', $rolesThatTakeSurveys));
    }

    /**
     * Survey takers who have not completed the survey yet.
     *
     * @return Builder<User>
     */
    public function pendingFor(Survey $survey): Builder
    {
        return $this->surveyTakers()
            ->whereDoesntHave('participations', fn (Builder $query) => $query->whereBelongsTo($survey));
    }

    /**
     * Remind every given user (in-app notification and e-mail, queued) and
     * return how many were reminded. A failure for one person is reported
     * and does not stop the others.
     *
     * @param  Builder<User>  $users
     */
    public function remind(Survey $survey, Builder $users): int
    {
        $daysLeft = $survey->daysLeft();
        $reminded = 0;

        foreach ($users->lazyById(200) as $user) {
            try {
                $user->notify(new SurveyDeadlineReminder($survey, $daysLeft));
                $reminded++;
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return $reminded;
    }

    /**
     * Where the reminder e-mails come from (MAIL_FROM_ADDRESS, e.g.
     * mti.communication@merdekabattery.com), and whether e-mail leaves the
     * application at all: the "log" and "array" mailers only write it down.
     *
     * @return array{address: string, name: string, delivered: bool}
     */
    public function emailSender(): array
    {
        $transport = config('mail.mailers.'.config('mail.default').'.transport');

        return [
            'address' => (string) config('mail.from.address'),
            'name' => (string) config('mail.from.name'),
            'delivered' => ! in_array($transport, ['log', 'array'], true),
        ];
    }

    /**
     * Remind by hand everyone who has not completed the survey (no ids), or
     * the chosen people among them, and record it. Chosen people who
     * completed it already, are inactive or do not take surveys are skipped.
     *
     * @param  array<int, int>|null  $userIds
     */
    public function sendManually(Survey $survey, User $sender, ?array $userIds = null): SurveyManualReminder
    {
        $recipients = $this->pendingFor($survey)
            ->when($userIds !== null, fn (Builder $query) => $query->whereKey($userIds));
        $eligible = (clone $recipients)->count();

        return SurveyManualReminder::create([
            'survey_id' => $survey->id,
            'sent_by' => $sender->id,
            'audience' => $userIds === null ? SurveyManualReminder::AUDIENCE_ALL : SurveyManualReminder::AUDIENCE_CUSTOM,
            'recipients' => $this->remind($survey, $recipients),
            'skipped' => $userIds === null ? 0 : max(0, count(array_unique($userIds)) - $eligible),
            'sent_at' => now(),
        ]);
    }
}
