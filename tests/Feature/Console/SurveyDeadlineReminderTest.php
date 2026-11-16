<?php

namespace Tests\Feature\Console;

use App\Enums\SurveyStatus;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyParticipation;
use App\Models\UserManagement\Role;
use App\Models\UserManagement\User;
use App\Notifications\SurveyDeadlineReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class SurveyDeadlineReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_employees_who_have_not_completed_an_open_survey_are_reminded_14_days_before_its_end(): void
    {
        $survey = $this->surveyEndingIn(14);
        $pending = $this->departmentEmployee();
        $completed = $this->departmentEmployee();
        SurveyParticipation::create(['survey_id' => $survey->id, 'user_id' => $completed->id, 'submitted_at' => now()]);
        $inactive = $this->departmentEmployee(['is_active' => false]);
        $withoutSurveyAccess = User::factory()->withRole(Role::factory()->create())->create(['department_id' => $this->respondentDepartment()->id]);
        $outsideTheAssessedDepartments = $this->employee();
        Notification::fake();

        $this->artisan('survey:send-deadline-reminders')->assertSuccessful();

        Notification::assertSentTo(
            $pending,
            SurveyDeadlineReminder::class,
            fn (SurveyDeadlineReminder $notification, array $channels): bool => $notification->survey->is($survey)
                && $notification->daysLeft === 14
                && $channels === ['database', 'mail'],
        );
        Notification::assertNotSentTo([$completed, $inactive, $withoutSurveyAccess, $outsideTheAssessedDepartments], SurveyDeadlineReminder::class);
        $this->assertDatabaseHas('survey_reminders', ['survey_id' => $survey->id, 'days_before' => 14, 'recipients' => 1]);
    }

    public function test_no_reminder_is_sent_before_the_reminder_period(): void
    {
        $this->surveyEndingIn(15);
        $this->departmentEmployee();
        Notification::fake();

        $this->artisan('survey:send-deadline-reminders')->assertSuccessful();

        Notification::assertNothingSent();
        $this->assertDatabaseCount('survey_reminders', 0);
    }

    public function test_a_reminder_is_only_sent_once(): void
    {
        $this->surveyEndingIn(14);
        $employee = $this->departmentEmployee();
        Notification::fake();

        $this->artisan('survey:send-deadline-reminders');
        $this->travel(1)->days();
        $this->artisan('survey:send-deadline-reminders');

        Notification::assertSentToTimes($employee, SurveyDeadlineReminder::class, 1);
    }

    public function test_survey_opened_inside_the_reminder_period_is_reminded_on_the_first_run(): void
    {
        $this->surveyEndingIn(5);
        $employee = $this->departmentEmployee();
        Notification::fake();

        $this->artisan('survey:send-deadline-reminders');

        Notification::assertSentTo($employee, SurveyDeadlineReminder::class, fn (SurveyDeadlineReminder $notification): bool => $notification->daysLeft === 5);
    }

    /**
     * @return array<string, array{0: SurveyStatus}>
     */
    public static function surveysNotOpen(): array
    {
        return [
            'draft' => [SurveyStatus::Draft],
            'closed' => [SurveyStatus::Closed],
        ];
    }

    #[DataProvider('surveysNotOpen')]
    public function test_surveys_that_are_not_open_are_not_reminded(SurveyStatus $status): void
    {
        $this->surveyEndingIn(7)->update(['status' => $status]);
        $this->departmentEmployee();
        Notification::fake();

        $this->artisan('survey:send-deadline-reminders');

        Notification::assertNothingSent();
    }

    public function test_every_configured_reminder_day_is_sent_once(): void
    {
        config(['survey.reminder_days' => [14, 7]]);
        $this->surveyEndingIn(14);
        $employee = $this->departmentEmployee();
        Notification::fake();

        $this->artisan('survey:send-deadline-reminders');
        $this->travel(7)->days();
        $this->artisan('survey:send-deadline-reminders');
        $this->travel(1)->days();
        $this->artisan('survey:send-deadline-reminders');

        Notification::assertSentToTimes($employee, SurveyDeadlineReminder::class, 2);
        Notification::assertSentTo($employee, SurveyDeadlineReminder::class, fn (SurveyDeadlineReminder $notification): bool => $notification->daysLeft === 7);
    }

    public function test_dry_run_only_reports_how_many_employees_would_be_reminded(): void
    {
        $this->surveyEndingIn(14);
        $this->departmentEmployee();
        Notification::fake();

        $this->artisan('survey:send-deadline-reminders', ['--dry-run' => true])
            ->expectsOutputToContain('Audit 2026 (H-14, closes in 14 days): 1 employee(s) would be reminded.')
            ->assertSuccessful();

        Notification::assertNothingSent();
        $this->assertDatabaseCount('survey_reminders', 0);
    }

    public function test_reminder_email_and_in_app_notification_link_to_the_survey(): void
    {
        $survey = Survey::factory()->create(['title' => 'Operational Audit 2026', 'end_date' => '2026-11-27', 'estimated_minutes' => 15]);
        $notification = new SurveyDeadlineReminder($survey, 14);
        $employee = User::factory()->make(['name' => 'Ani']);

        $mail = $notification->toMail($employee);

        $this->assertSame('Reminder: Operational Audit 2026 closes in 14 days', $mail->subject);
        $this->assertSame('Complete the Survey', $mail->actionText);
        $this->assertSame(route('survey-form.show', $survey), $mail->actionUrl);
        $this->assertSame([
            'title' => 'Survey deadline approaching',
            'message' => 'Operational Audit 2026 closes in 14 days (27 Nov 2026). Please complete it before the deadline.',
            'url' => '/survey-form/'.$survey->id,
            'survey_id' => $survey->id,
            'days_left' => 14,
        ], $notification->toArray($employee));
    }

    #[TestWith([0, 'today'])]
    #[TestWith([1, 'tomorrow'])]
    #[TestWith([14, 'in 14 days'])]
    public function test_deadline_text_reads_naturally(int $daysLeft, string $expected): void
    {
        $this->assertSame($expected, Survey::deadlineText($daysLeft));
    }

    /**
     * An open survey that ends the given number of days from today.
     */
    protected function surveyEndingIn(int $days): Survey
    {
        return Survey::factory()->create([
            'title' => 'Audit 2026',
            'start_date' => today()->subDays(10),
            'end_date' => today()->addDays($days),
        ]);
    }
}
