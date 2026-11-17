<?php

namespace Tests\Feature\SurveyProgress;

use App\Enums\PermissionAction;
use App\Models\Organization\Department;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyDraft;
use App\Models\Survey\SurveyParticipation;
use App\Models\Survey\SurveyStart;
use App\Models\UserManagement\Menu;
use App\Models\UserManagement\MenuRolePermission;
use App\Models\UserManagement\Role;
use App\Models\UserManagement\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurveyProgressTest extends TestCase
{
    use RefreshDatabase;

    protected Survey $survey;

    protected User $admin;

    /**
     * @var array<string, User>
     */
    protected array $people;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAccessControl();
        [$this->survey] = $this->openSurveyFor([Department::factory()->create()]);
        $this->admin = $this->superAdmin(['name' => 'Zara Admin']);

        $this->people = [
            'not_started' => $this->departmentEmployee(['name' => 'Ani Belum']),
            'started' => $this->departmentEmployee(['name' => 'Budi Buka']),
            'draft' => $this->departmentEmployee(['name' => 'Citra Draf']),
            'completed' => $this->departmentEmployee(['name' => 'Dedi Selesai']),
        ];
        SurveyStart::factory()->for($this->survey)->for($this->people['started'])->create();
        SurveyDraft::factory()->for($this->survey)->for($this->people['draft'])->create();
        SurveyParticipation::create(['survey_id' => $this->survey->id, 'user_id' => $this->people['completed']->id, 'submitted_at' => now()]);
        $this->departmentEmployee(['name' => 'Eko Nonaktif', 'is_active' => false]);
        $this->employee(['name' => 'Fajar Operasional']);
    }

    public function test_the_summary_counts_people_by_status(): void
    {
        $this->actingAs($this->admin)
            ->get(route('survey-progress.index'))
            ->assertOk()
            ->assertViewHas('survey', fn (Survey $survey): bool => $survey->is($this->survey))
            ->assertViewHas('summary', [
                'eligible' => 4,
                'not_started' => 1,
                'in_progress' => 2,
                'saved_draft' => 1,
                'completed' => 1,
                'completion_rate' => 25.0,
            ]);
    }

    public function test_the_summary_cards_show_their_icons_not_their_names(): void
    {
        $page = $this->actingAs($this->admin)->get(route('survey-progress.index'))->assertOk();

        foreach (['users', 'circle-dashed', 'loader', 'circle-check'] as $icon) {
            $page->assertSee('<i class="ti ti-'.$icon.'" aria-hidden="true"></i>', false);
        }

        $this->assertDoesNotMatchRegularExpression('#<div class="summary-icon[^"]*">\s*users\s*<span#', (string) $page->getContent());
    }

    public function test_each_person_is_listed_with_their_status(): void
    {
        $rows = $this->actingAs($this->admin)
            ->getJson(route('survey-progress.list', ['survey_id' => $this->survey->id]))
            ->assertOk()
            ->json('data');

        $this->assertSame(
            [
                'Ani Belum' => ['not_started', false],
                'Budi Buka' => ['in_progress', false],
                'Citra Draf' => ['in_progress', true],
                'Dedi Selesai' => ['completed', false],
            ],
            collect($rows)->mapWithKeys(fn (array $row): array => [$row['name'] => [$row['status'], $row['saved_draft']]])->all(),
        );
        $this->assertSame([$this->respondentDepartment()->name], collect($rows)->pluck('department')->unique()->values()->all());
        $this->assertNull(collect($rows)->firstWhere('name', 'Ani Belum')['last_activity']);
        $this->assertNotNull(collect($rows)->firstWhere('name', 'Dedi Selesai')['last_activity']);
    }

    public function test_each_person_shows_with_their_photo_or_initials(): void
    {
        $this->people['not_started']->forceFill(['photo_path' => 'user-photos/'.$this->people['not_started']->id.'.webp', 'photo_updated_at' => now()])->save();

        $rows = collect($this->actingAs($this->admin)
            ->getJson(route('survey-progress.list', ['survey_id' => $this->survey->id]))
            ->assertOk()
            ->json('data'))->keyBy('name');

        $this->assertSame($this->people['not_started']->fresh()->photoUrl(), $rows['Ani Belum']['photo_url']);
        $this->assertNull($rows['Budi Buka']['photo_url']);
        $this->assertSame('BB', $rows['Budi Buka']['initials']);
    }

    public function test_the_list_can_be_narrowed_to_one_status(): void
    {
        $this->actingAs($this->admin)
            ->getJson(route('survey-progress.list', ['survey_id' => $this->survey->id, 'status' => 'in_progress']))
            ->assertOk()
            ->assertJsonPath('data.*.name', ['Budi Buka', 'Citra Draf']);

        $this->actingAs($this->admin)
            ->getJson(route('survey-progress.list', ['survey_id' => $this->survey->id, 'status' => 'not_started', 'search' => 'ani']))
            ->assertOk()
            ->assertJsonPath('data.*.name', ['Ani Belum']);
    }

    public function test_another_survey_can_be_followed(): void
    {
        $closed = Survey::factory()->closed()->create(['title' => 'Operational Audit 2025']);
        SurveyParticipation::create(['survey_id' => $closed->id, 'user_id' => $this->people['not_started']->id, 'submitted_at' => now()->subYear()]);

        $this->actingAs($this->admin)
            ->get(route('survey-progress.index', ['survey' => $closed->id]))
            ->assertOk()
            ->assertViewHas('survey', fn (Survey $survey): bool => $survey->is($closed))
            ->assertViewHas('summary', fn (array $summary): bool => $summary['completed'] === 1 && $summary['in_progress'] === 0);
    }

    public function test_those_who_send_reminders_remind_from_the_progress_of_an_open_survey(): void
    {
        $this->actingAs($this->admin)
            ->get(route('survey-progress.index'))
            ->assertOk()
            ->assertViewHas('canSendReminders', true)
            ->assertViewHas('pendingCount', 3)
            ->assertSee('id="progressReminderModal"', false)
            ->assertSeeInOrder(['Send by', 'Notification in the application', 'E-mail']);
    }

    public function test_no_reminder_is_offered_for_a_survey_that_is_not_open(): void
    {
        $closed = Survey::factory()->closed()->create();

        $this->actingAs($this->admin)
            ->get(route('survey-progress.index', ['survey' => $closed->id]))
            ->assertOk()
            ->assertViewHas('canSendReminders', false)
            ->assertDontSee('id="progressReminderModal"', false);
    }

    public function test_no_reminder_is_offered_to_those_who_may_not_send_one(): void
    {
        MenuRolePermission::query()
            ->where('role_id', Role::query()->where('name', 'Super Admin')->value('id'))
            ->where('menu_id', Menu::idForKey('survey-management.reminder'))
            ->where('permission_id', PermissionAction::Create->value)
            ->delete();

        $this->actingAs($this->admin)
            ->get(route('survey-progress.index'))
            ->assertOk()
            ->assertViewHas('canSendReminders', false)
            ->assertDontSee('id="progressReminderModal"', false);
    }

    public function test_only_survey_administrators_see_the_progress_of_others(): void
    {
        $this->actingAs($this->people['not_started'])->get(route('survey-progress.index'))->assertForbidden();
        $this->actingAs($this->manager())
            ->getJson(route('survey-progress.list', ['survey_id' => $this->survey->id]))
            ->assertForbidden();
    }
}
