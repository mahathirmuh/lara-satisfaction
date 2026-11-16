<?php

namespace Tests\Feature\SurveyManagement;

use App\Models\Survey\Survey;
use App\Models\Survey\SurveyManualReminder;
use App\Models\Survey\SurveyParticipation;
use App\Models\Survey\SurveyReminder;
use App\Models\UserManagement\Role;
use App\Models\UserManagement\User;
use App\Notifications\SurveyDeadlineReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
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
        $pending = $this->employee();
        $completed = $this->completedEmployee();
        $inactive = $this->employee(['is_active' => false]);
        $withoutSurveyAccess = User::factory()->withRole(Role::factory()->create())->create();
        Notification::fake();

        $this->actingAs($this->admin)
            ->postJson(route('survey-management.reminder.store'), ['survey_id' => $this->survey->id, 'audience' => 'all'])
            ->assertOk()
            ->assertJsonPath('message', 'Reminder sent to 2 people.');

        Notification::assertSentTo(
            [$pending, $this->admin],
            SurveyDeadlineReminder::class,
            fn (SurveyDeadlineReminder $notification, array $channels): bool => $notification->survey->is($this->survey)
                && $channels === ['database', 'mail'],
        );
        Notification::assertNotSentTo([$completed, $inactive, $withoutSurveyAccess], SurveyDeadlineReminder::class);
        $this->assertDatabaseHas('survey_manual_reminders', [
            'survey_id' => $this->survey->id,
            'sent_by' => $this->admin->id,
            'audience' => 'all',
            'recipients' => 2,
            'skipped' => 0,
        ]);
    }

    public function test_only_the_chosen_people_who_can_be_reminded_are(): void
    {
        $chosen = $this->employee();
        $notChosen = $this->employee();
        $completed = $this->completedEmployee();
        Notification::fake();

        $this->actingAs($this->admin)
            ->postJson(route('survey-management.reminder.store'), [
                'survey_id' => $this->survey->id,
                'audience' => 'custom',
                'user_ids' => [$chosen->id, $completed->id],
            ])
            ->assertOk()
            ->assertJsonPath('data', ['recipients' => 1, 'skipped' => 1]);

        Notification::assertSentTo($chosen, SurveyDeadlineReminder::class);
        Notification::assertNotSentTo([$notChosen, $completed, $this->admin], SurveyDeadlineReminder::class);
        $this->assertDatabaseHas('survey_manual_reminders', ['audience' => 'custom', 'recipients' => 1, 'skipped' => 1]);
    }

    /**
     * @return array<string, array{0: string, 1: array<string, mixed>, 2: string}>
     */
    public static function refusedSends(): array
    {
        return [
            'no one chosen' => ['open', ['audience' => 'custom'], 'user_ids'],
            'only people who completed it chosen' => ['open', ['audience' => 'custom', 'user_ids' => 'completed'], 'user_ids'],
            'everyone completed it' => ['open', ['audience' => 'all', 'everyone_completed' => true], 'audience'],
            'survey not open' => ['closed', ['audience' => 'all'], 'survey_id'],
            'unknown audience' => ['open', ['audience' => 'managers'], 'audience'],
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

        if ($payload['everyone_completed'] ?? false) {
            SurveyParticipation::create(['survey_id' => $survey->id, 'user_id' => $this->admin->id, 'submitted_at' => now()]);
            unset($payload['everyone_completed']);
        }

        if (($payload['user_ids'] ?? null) === 'completed') {
            $payload['user_ids'] = [$completed->id];
        }

        Notification::fake();

        $this->actingAs($this->admin)
            ->postJson(route('survey-management.reminder.store'), ['survey_id' => $survey->id, ...$payload])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$invalidField]);

        Notification::assertNothingSent();
        $this->assertDatabaseCount('survey_manual_reminders', 0);
    }

    public function test_the_people_search_shows_who_completed_the_survey_already(): void
    {
        $pending = $this->employee(['name' => 'Budi Santoso', 'email' => 'budi.santoso@merdekabattery.com']);
        $completed = $this->completedEmployee(attributes: ['name' => 'Budi Hartono', 'email' => 'budi.hartono@merdekabattery.com']);
        $this->employee(['name' => 'Budi Nonaktif', 'is_active' => false]);

        $this->actingAs($this->admin)
            ->getJson(route('survey-management.reminder.recipients', ['search' => 'budi', 'survey_id' => $this->survey->id]))
            ->assertOk()
            ->assertExactJson(['data' => [
                ['id' => $completed->id, 'text' => 'Budi Hartono — budi.hartono@merdekabattery.com (completed)', 'disabled' => true],
                ['id' => $pending->id, 'text' => 'Budi Santoso — budi.santoso@merdekabattery.com', 'disabled' => false],
            ]]);
    }

    public function test_the_history_lists_reminders_sent_by_hand_and_scheduled_ones(): void
    {
        SurveyManualReminder::factory()->for($this->survey)->custom(recipients: 1)->create(['sent_by' => $this->admin->id]);
        SurveyReminder::create(['survey_id' => $this->survey->id, 'days_before' => 14, 'recipients' => 120, 'sent_at' => now()->subDay()]);

        $this->actingAs($this->admin)
            ->get(route('survey-management.reminder.index'))
            ->assertOk()
            ->assertViewHas('pendingCounts', fn ($counts): bool => $counts->all() === [$this->survey->id => 1])
            ->assertSeeInOrder(['Chosen people', 'Admin Survei', 'Scheduled (14 days before the end)', '120', 'System']);
    }

    public function test_only_survey_administrators_can_send_reminders(): void
    {
        $employee = $this->employee();

        $this->actingAs($employee)->get(route('survey-management.reminder.index'))->assertForbidden();
        $this->actingAs($this->manager())
            ->postJson(route('survey-management.reminder.store'), ['survey_id' => $this->survey->id, 'audience' => 'all'])
            ->assertForbidden();
    }

    /**
     * An employee who completed the survey (this test's survey by default).
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function completedEmployee(?Survey $survey = null, array $attributes = []): User
    {
        $employee = $this->employee($attributes);
        SurveyParticipation::create(['survey_id' => ($survey ?? $this->survey)->id, 'user_id' => $employee->id, 'submitted_at' => now()]);

        return $employee;
    }
}
