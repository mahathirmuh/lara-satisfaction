<?php

namespace App\Services\Survey;

use App\Enums\PermissionAction;
use App\Enums\ReminderChannel;
use App\Models\Organization\Company;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyManualReminder;
use App\Models\UserManagement\Menu;
use App\Models\UserManagement\MenuRolePermission;
use App\Models\UserManagement\User;
use App\Notifications\SurveyDeadlineReminder;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Throwable;

/**
 * Who the survey respondents are, and the reminding: the active employees of
 * the respondent companies (config survey.respondent_companies, MTI) and of
 * the assessed departments (department_id), all imported daily from the
 * directory, whose role may fill in surveys (Create on My Survey). The same
 * people are reminded, listed in Survey Progress and counted in the
 * participation rate. Used by the daily scheduled reminders and by the
 * reminders sent by hand from Survey Management > Reminders.
 */
class SurveyReminders
{
    /**
     * The respondents: active people whose role may fill in surveys and who
     * work in an assessed department, or are employees (with an employee ID) of
     * a respondent company.
     *
     * @return Builder<User>
     */
    public function surveyTakers(): Builder
    {
        $rolesThatTakeSurveys = MenuRolePermission::query()
            ->where('menu_id', Menu::idForKey('survey-form'))
            ->where('permission_id', PermissionAction::Create->value)
            ->select('role_id');
        $respondentCompanies = Company::query()->whereIn('code', (array) config('survey.respondent_companies', []))->select('id');

        return User::query()
            ->active()
            ->where(fn (Builder $query) => $query
                ->whereNotNull('department_id')
                ->orWhere(fn (Builder $query) => $query->whereIn('company_id', $respondentCompanies)->whereNotNull('employee_id')))
            ->whereHas('roles', fn (Builder $query) => $query->whereIn('roles.id', $rolesThatTakeSurveys));
    }

    /**
     * The names of the respondent companies, for the texts that say who the
     * respondents are ("every employee of PT Merdeka Tsingshan Indonesia and of
     * the assessed departments").
     */
    public static function respondentCompanyNames(): string
    {
        return once(fn (): string => Company::query()
            ->whereIn('code', (array) config('survey.respondent_companies', []))
            ->orderBy('name')
            ->pluck('name')
            ->implode(', '));
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
     * Remind every given user (in-app notification and e-mail by default,
     * queued), and count who was reminded and the e-mails the mail server
     * refused. A failure for one person is reported and does not stop the
     * others. The e-mail goes last (ReminderChannel::notificationChannels), so
     * when only it is refused the notification in the application did arrive
     * and the person counts as reminded. (Queued, as in production, a refused
     * e-mail fails later in the queue and is not counted here.)
     *
     * @param  Builder<User>  $users
     * @param  array<int, ReminderChannel>  $channels
     * @return array{reminded: int, emailFailures: int}
     */
    public function remind(Survey $survey, Builder $users, array $channels = [ReminderChannel::App, ReminderChannel::Email]): array
    {
        $daysLeft = $survey->daysLeft();
        $notificationChannels = ReminderChannel::notificationChannels($channels);
        $reminded = 0;
        $emailFailures = 0;

        foreach ($users->lazyById(200) as $user) {
            try {
                $user->notify(new SurveyDeadlineReminder($survey, $daysLeft, $notificationChannels));
                $reminded++;
            } catch (TransportExceptionInterface $exception) {
                report($exception);
                $emailFailures++;
                $reminded += in_array('database', $notificationChannels, true) ? 1 : 0;
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return ['reminded' => $reminded, 'emailFailures' => $emailFailures];
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
     * the chosen people among them, the chosen ways, and record it. Chosen
     * people who completed it already, are inactive or do not take surveys
     * are skipped. Returns the send and the e-mails the mail server refused.
     *
     * @param  array<int, int>|null  $userIds
     * @param  array<int, ReminderChannel>  $channels
     * @return array{send: SurveyManualReminder, emailFailures: int}
     */
    public function sendManually(Survey $survey, User $sender, ?array $userIds = null, array $channels = [ReminderChannel::App, ReminderChannel::Email]): array
    {
        $recipients = $this->pendingFor($survey)
            ->when($userIds !== null, fn (Builder $query) => $query->whereKey($userIds));
        $eligible = (clone $recipients)->count();
        $outcome = $this->remind($survey, $recipients, $channels);

        $send = SurveyManualReminder::create([
            'survey_id' => $survey->id,
            'sent_by' => $sender->id,
            'audience' => $userIds === null ? SurveyManualReminder::AUDIENCE_ALL : SurveyManualReminder::AUDIENCE_CUSTOM,
            'recipients' => $outcome['reminded'],
            'skipped' => $userIds === null ? 0 : max(0, count(array_unique($userIds)) - $eligible),
            'via_app' => in_array(ReminderChannel::App, $channels, true),
            'via_email' => in_array(ReminderChannel::Email, $channels, true),
            'sent_at' => now(),
        ]);

        return ['send' => $send, 'emailFailures' => $outcome['emailFailures']];
    }
}
