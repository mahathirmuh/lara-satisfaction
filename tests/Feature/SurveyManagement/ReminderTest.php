<?php

namespace Tests\Feature\SurveyManagement;

use App\Enums\PermissionAction;
use App\Enums\ReminderChannel;
use App\Models\Log\AuditLog;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyManualReminder;
use App\Models\Survey\SurveyParticipation;
use App\Models\Survey\SurveyReminder;
use App\Models\Survey\SurveyReminderSetting;
use App\Models\UserManagement\Menu;
use App\Models\UserManagement\MenuRolePermission;
use App\Models\UserManagement\Role;
use App\Models\UserManagement\User;
use App\Notifications\SurveyDeadlineReminder;
use App\Services\Audit\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Tests\TestCase;

class ReminderTest extends TestCase
{
    use RefreshDatabase;

    protected Survey $survey;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAccessControl();
        $this->survey = Survey::factory()->create(['title' => 'Operational Audit 2026']);
        $this->admin = $this->superAdmin(['name' => 'Admin Survei']);
    }

    public function test_everyone_who_has_not_completed_the_survey_is_reminded(): void
    {
        $pending = [$this->departmentEmployee(), $this->departmentEmployee()];
        $completed = $this->completedEmployee();
        $inactive = $this->departmentEmployee(['is_active' => false]);
        $withoutSurveyAccess = User::factory()->withRole(Role::factory()->create())->create(['department_id' => $this->respondentDepartment()->id]);
        $outsideTheAssessedDepartments = $this->employee();
        Notification::fake();

        $this->actingAs($this->admin)
            ->postJson(route('survey-management.reminder.store'), ['survey_id' => $this->survey->id, 'audience' => 'all', 'channels' => ['app', 'email']])
            ->assertOk()
            ->assertJsonPath('message', 'Reminder sent to 2 people.');

        Notification::assertSentTo(
            $pending,
            SurveyDeadlineReminder::class,
            fn (SurveyDeadlineReminder $notification, array $channels): bool => $notification->survey->is($this->survey)
                && $channels === ['database', 'mail'],
        );
        Notification::assertNotSentTo([$completed, $inactive, $withoutSurveyAccess, $outsideTheAssessedDepartments, $this->admin], SurveyDeadlineReminder::class);
        $this->assertDatabaseHas('survey_manual_reminders', [
            'survey_id' => $this->survey->id,
            'sent_by' => $this->admin->id,
            'audience' => 'all',
            'recipients' => 2,
            'skipped' => 0,
            'via_app' => true,
            'via_email' => true,
        ]);
    }

    public function test_only_the_chosen_people_who_can_be_reminded_are(): void
    {
        $chosen = $this->departmentEmployee();
        $notChosen = $this->departmentEmployee();
        $completed = $this->completedEmployee();
        Notification::fake();

        $this->actingAs($this->admin)
            ->postJson(route('survey-management.reminder.store'), [
                'survey_id' => $this->survey->id,
                'audience' => 'custom',
                'user_ids' => [$chosen->id, $completed->id],
                'channels' => ['app', 'email'],
            ])
            ->assertOk()
            ->assertJsonPath('data', ['recipients' => 1, 'skipped' => 1, 'email_failures' => 0]);

        Notification::assertSentTo($chosen, SurveyDeadlineReminder::class);
        Notification::assertNotSentTo([$notChosen, $completed, $this->admin], SurveyDeadlineReminder::class);
        $this->assertDatabaseHas('survey_manual_reminders', ['audience' => 'custom', 'recipients' => 1, 'skipped' => 1]);
    }

    /**
     * @return array<string, array{0: ReminderChannel, 1: array<int, string>}>
     */
    public static function singleWays(): array
    {
        return [
            'the notification in the application only' => [ReminderChannel::App, ['database']],
            'the e-mail only' => [ReminderChannel::Email, ['mail']],
        ];
    }

    /**
     * @param  array<int, string>  $notificationChannels
     */
    #[DataProvider('singleWays')]
    public function test_a_reminder_goes_only_the_way_chosen(ReminderChannel $way, array $notificationChannels): void
    {
        $employee = $this->departmentEmployee();
        Notification::fake();

        $this->actingAs($this->admin)
            ->postJson(route('survey-management.reminder.store'), [
                'survey_id' => $this->survey->id,
                'audience' => 'custom',
                'user_ids' => [$employee->id],
                'channels' => [$way->value],
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Reminder sent to 1 person.');

        Notification::assertSentTo($employee, SurveyDeadlineReminder::class, fn (SurveyDeadlineReminder $notification, array $channels): bool => $channels === $notificationChannels);
        $this->assertDatabaseHas('survey_manual_reminders', [
            'recipients' => 1,
            'via_app' => $way === ReminderChannel::App,
            'via_email' => $way === ReminderChannel::Email,
        ]);
    }

    public function test_a_refused_e_mail_is_said_and_the_notification_still_counts(): void
    {
        $employee = $this->departmentEmployee();
        Mail::extend('refusing', fn (): AbstractTransport => new class extends AbstractTransport
        {
            protected function doSend(SentMessage $message): void
            {
                throw new TransportException('ErrorAccessDenied');
            }

            public function __toString(): string
            {
                return 'refusing';
            }
        });
        config(['mail.mailers.refusing' => ['transport' => 'refusing'], 'mail.default' => 'refusing']);
        Exceptions::fake();

        $this->actingAs($this->admin)
            ->postJson(route('survey-management.reminder.store'), [
                'survey_id' => $this->survey->id,
                'audience' => 'custom',
                'user_ids' => [$employee->id],
                'channels' => ['app', 'email'],
            ])
            ->assertOk()
            ->assertJsonPath('warning', true)
            ->assertJsonPath('data.recipients', 1)
            ->assertJsonPath('data.email_failures', 1)
            ->assertJsonPath('message', 'Reminder sent to 1 person. 1 e-mail could not be sent: the mail server refused it. The reason is in the error log.');

        $this->assertSame(1, $employee->notifications()->count());
        Exceptions::assertReported(TransportException::class);
    }

    /**
     * @return array<string, array{0: string, 1: array<string, mixed>, 2: string}>
     */
    public static function refusedSends(): array
    {
        return [
            'no one chosen' => ['open', ['audience' => 'custom'], 'user_ids'],
            'only people who completed it chosen' => ['open', ['audience' => 'custom', 'user_ids' => 'completed'], 'user_ids'],
            'everyone completed it' => ['open', ['audience' => 'all'], 'audience'],
            'survey not open' => ['closed', ['audience' => 'all'], 'survey_id'],
            'unknown audience' => ['open', ['audience' => 'managers'], 'audience'],
            'no way to remind' => ['open', ['audience' => 'all', 'channels' => []], 'channels'],
            'an unknown way to remind' => ['open', ['audience' => 'all', 'channels' => ['sms']], 'channels.0'],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    #[DataProvider('refusedSends')]
    public function test_a_send_with_no_one_to_remind_is_refused(string $surveyState, array $payload, string $invalidField): void
    {
        $survey = $surveyState === 'closed' ? Survey::factory()->closed()->create() : $this->survey;
        $completed = $this->completedEmployee($survey);

        if (($payload['user_ids'] ?? null) === 'completed') {
            $payload['user_ids'] = [$completed->id];
        }

        Notification::fake();

        $this->actingAs($this->admin)
            ->postJson(route('survey-management.reminder.store'), ['survey_id' => $survey->id, 'channels' => ['app', 'email'], ...$payload])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$invalidField]);

        Notification::assertNothingSent();
        $this->assertDatabaseCount('survey_manual_reminders', 0);
    }

    public function test_the_people_search_shows_who_completed_the_survey_already(): void
    {
        $pending = $this->departmentEmployee(['name' => 'Budi Santoso', 'email' => 'budi.santoso@merdekabattery.com', 'employee_id' => 'MTI1002']);
        $completed = $this->completedEmployee(attributes: ['name' => 'Budi Hartono', 'email' => 'budi.hartono@merdekabattery.com', 'employee_id' => 'MTI1001']);
        $this->departmentEmployee(['name' => 'Budi Nonaktif', 'is_active' => false]);

        // The name, e-mail and employee ID apart, for the two lines of a person in the list.
        $this->actingAs($this->admin)
            ->getJson(route('survey-management.reminder.recipients', ['search' => 'budi', 'survey_id' => $this->survey->id]))
            ->assertOk()
            ->assertExactJson(['data' => [
                ['id' => $completed->id, 'text' => 'Budi Hartono — budi.hartono@merdekabattery.com (completed)', 'name' => 'Budi Hartono',
                    'email' => 'budi.hartono@merdekabattery.com', 'employee_id' => 'MTI1001', 'disabled' => true],
                ['id' => $pending->id, 'text' => 'Budi Santoso — budi.santoso@merdekabattery.com', 'name' => 'Budi Santoso',
                    'email' => 'budi.santoso@merdekabattery.com', 'employee_id' => 'MTI1002', 'disabled' => false],
            ]]);
    }

    public function test_the_history_lists_reminders_sent_by_hand_and_scheduled_ones(): void
    {
        $this->departmentEmployee();
        SurveyManualReminder::factory()->for($this->survey)->custom(recipients: 1)->emailOnly()->create(['sent_by' => $this->admin->id]);
        SurveyReminder::create(['survey_id' => $this->survey->id, 'days_before' => 14, 'recipients' => 120, 'sent_at' => now()->subDay()]);

        $this->actingAs($this->admin)
            ->get(route('survey-management.reminder.index'))
            ->assertOk()
            ->assertViewHas('pendingCounts', fn ($counts): bool => $counts->all() === [$this->survey->id => 1])
            ->assertViewHas('history', fn ($history): bool => $history->pluck('channels')->all() === ['E-mail', 'Notification in the application · E-mail'])
            ->assertSeeInOrder(['Chosen people', 'E-mail', 'Admin Survei', 'Scheduled (14 days before the end)', 'Notification in the application · E-mail', '120', 'System']);
    }

    public function test_the_automatic_reminders_are_turned_off_and_on_again(): void
    {
        $this->actingAs($this->admin)
            ->putJson(route('survey-management.reminder.automatic'), ['automatic' => true])
            ->assertOk()
            ->assertJsonPath('message', 'Automatic reminders are on.');

        $this->assertDatabaseCount('survey_reminder_settings', 0);

        $this->actingAs($this->admin)
            ->putJson(route('survey-management.reminder.automatic'), ['automatic' => false])
            ->assertOk()
            ->assertJsonPath('message', 'Automatic reminders are off.')
            ->assertJsonPath('data.automatic', false)
            ->assertJsonPath('data.changed', fn (string $changed): bool => str_starts_with($changed, 'Last changed by Admin Survei, '));

        $this->assertFalse(SurveyReminderSetting::automaticRemindersOn());

        $this->actingAs($this->admin)
            ->putJson(route('survey-management.reminder.automatic'), ['automatic' => true])
            ->assertOk()
            ->assertJsonPath('data.automatic', true);

        $this->assertTrue(SurveyReminderSetting::automaticRemindersOn());
        $this->assertSame([
            [AuditLogger::AUTOMATIC_REMINDERS_OFF, ['Automatic reminders' => true], ['Automatic reminders' => false], $this->admin->id],
            [AuditLogger::AUTOMATIC_REMINDERS_ON, ['Automatic reminders' => false], ['Automatic reminders' => true], $this->admin->id],
        ], AuditLog::query()->orderBy('id')->get()->map(fn (AuditLog $entry): array => [
            $entry->action, $entry->old_values, $entry->new_values, $entry->user_id,
        ])->all());
    }

    public function test_switching_the_automatic_reminders_needs_the_update_permission_of_reminders(): void
    {
        $this->revokeUpdateOnReminders('Super Admin');

        $this->actingAs($this->admin)
            ->get(route('survey-management.reminder.index'))
            ->assertOk()
            ->assertViewHas('can', fn (array $can): bool => $can['create'] && ! $can['update']);
        $this->actingAs($this->admin)
            ->putJson(route('survey-management.reminder.automatic'), ['automatic' => false])
            ->assertForbidden();
        $this->actingAs($this->departmentEmployee())
            ->putJson(route('survey-management.reminder.automatic'), ['automatic' => false])
            ->assertForbidden();

        $this->assertTrue(SurveyReminderSetting::automaticRemindersOn());
    }

    public function test_after_the_upgrade_the_roles_that_send_reminders_may_switch_the_automatic_ones(): void
    {
        $this->revokeUpdateOnReminders('Super Admin');

        (require database_path('migrations/2026_11_17_073830_allow_reminder_senders_to_switch_automatic_reminders.php'))->up();

        $this->assertSame(['IT Admin', 'Super Admin'], Role::query()
            ->whereIn('id', MenuRolePermission::query()
                ->where('menu_id', Menu::idForKey('survey-management.reminder'))
                ->where('permission_id', PermissionAction::Update->value)
                ->select('role_id'))
            ->orderBy('name')
            ->pluck('name')
            ->all());
    }

    public function test_the_page_shows_the_switch_and_where_the_e_mails_come_from(): void
    {
        SurveyReminderSetting::factory()->off()->create(['updated_by' => $this->admin->id]);
        config(['mail.default' => 'microsoft-graph', 'mail.from' => ['address' => 'mti.communication@merdekabattery.com', 'name' => 'Employee Satisfaction Survey']]);

        $this->actingAs($this->admin)
            ->get(route('survey-management.reminder.index'))
            ->assertOk()
            ->assertSeeInOrder([
                'Automatic Reminders',
                'Off',
                'Every day at 08:00, everyone who has not completed an open survey is reminded 14 days before it closes',
                'Reminder e-mails are sent from Employee Satisfaction Survey <mti.communication@merdekabattery.com>.',
                'Last changed by Admin Survei',
            ]);

        config(['mail.default' => 'log']);

        $this->actingAs($this->admin)
            ->get(route('survey-management.reminder.index'))
            ->assertSee('E-mails are not sent yet: the application only writes them to its log (MAIL_MAILER).');
    }

    public function test_only_survey_administrators_can_send_reminders(): void
    {
        $employee = $this->departmentEmployee();

        $this->actingAs($employee)->get(route('survey-management.reminder.index'))->assertForbidden();
        $this->actingAs($this->manager())
            ->postJson(route('survey-management.reminder.store'), ['survey_id' => $this->survey->id, 'audience' => 'all', 'channels' => ['app']])
            ->assertForbidden();
    }

    /**
     * Takes the Update permission of the Reminders menu from a role, as before
     * the automatic reminders could be switched.
     */
    protected function revokeUpdateOnReminders(string $roleName): void
    {
        MenuRolePermission::query()
            ->where('role_id', Role::query()->where('name', $roleName)->value('id'))
            ->where('menu_id', Menu::idForKey('survey-management.reminder'))
            ->where('permission_id', PermissionAction::Update->value)
            ->delete();
    }

    /**
     * An employee who completed the survey (this test's survey by default).
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function completedEmployee(?Survey $survey = null, array $attributes = []): User
    {
        $employee = $this->departmentEmployee($attributes);
        SurveyParticipation::create(['survey_id' => ($survey ?? $this->survey)->id, 'user_id' => $employee->id, 'submitted_at' => now()]);

        return $employee;
    }
}
