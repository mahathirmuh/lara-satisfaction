<?php

namespace Tests\Feature\SurveyForm;

use App\Enums\ParticipationStatus;
use App\Models\Organization\Department;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyDraft;
use App\Models\Survey\SurveyParticipation;
use App\Models\Survey\SurveyStart;
use App\Models\UserManagement\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * "My Survey" shows where the employee stands with each open survey:
 * Not Started -> In Progress (Saved as draft) -> Completed.
 */
class ParticipationStatusTest extends TestCase
{
    use RefreshDatabase;

    protected Survey $survey;

    protected User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        [$this->survey] = $this->openSurveyFor([Department::factory()->create()]);
        $this->employee = $this->employee();
    }

    /**
     * What is recorded => status, texts shown and texts not shown.
     *
     * @return array<string, array{0: string|null, 1: ParticipationStatus, 2: array<int, string>, 3: array<int, string>}>
     */
    public static function standings(): array
    {
        return [
            'nothing yet' => [null, ParticipationStatus::NotStarted, ['Not Started', 'Start Survey'], ['Saved as draft', 'Continue Survey']],
            'opened the form' => ['start', ParticipationStatus::InProgress, ['In Progress', 'Continue Survey'], ['Saved as draft']],
            'saved a draft' => ['draft', ParticipationStatus::InProgress, ['In Progress', 'Saved as draft', 'Continue Survey'], []],
            'submitted' => ['participation', ParticipationStatus::Completed, ['Completed', 'Thank you for participating'], ['Saved as draft', 'Continue Survey']],
        ];
    }

    /**
     * @param  array<int, string>  $shown
     * @param  array<int, string>  $hidden
     */
    #[DataProvider('standings')]
    public function test_my_survey_shows_where_the_employee_stands(?string $recorded, ParticipationStatus $status, array $shown, array $hidden): void
    {
        match ($recorded) {
            'start' => SurveyStart::factory()->for($this->survey)->for($this->employee)->create(),
            'draft' => SurveyDraft::factory()->for($this->survey)->for($this->employee)->create(),
            'participation' => SurveyParticipation::create(['survey_id' => $this->survey->id, 'user_id' => $this->employee->id, 'submitted_at' => now()]),
            default => null,
        };

        $response = $this->actingAs($this->employee)
            ->get(route('survey-form.index'))
            ->assertOk()
            ->assertViewHas('surveys', fn ($surveys): bool => $surveys->sole()->participation_status === $status);

        foreach ($shown as $text) {
            $response->assertSee($text);
        }

        foreach ($hidden as $text) {
            $response->assertDontSee($text);
        }
    }

    public function test_opening_the_form_puts_the_survey_in_progress_once(): void
    {
        $this->actingAs($this->employee)->get(route('survey-form.show', $this->survey))->assertOk();
        $startedAt = SurveyStart::query()->sole()->started_at;

        $this->travel(2)->hours();
        $this->actingAs($this->employee)->get(route('survey-form.show', $this->survey))->assertOk();

        $this->assertTrue(SurveyStart::query()->sole()->started_at->equalTo($startedAt));
        $this->actingAs($this->employee)
            ->get(route('survey-form.index'))
            ->assertViewHas('surveys', fn ($surveys): bool => $surveys->sole()->participation_status === ParticipationStatus::InProgress);
    }

    public function test_a_completed_survey_is_not_started_again(): void
    {
        SurveyParticipation::create(['survey_id' => $this->survey->id, 'user_id' => $this->employee->id, 'submitted_at' => now()]);

        $this->actingAs($this->employee)
            ->get(route('survey-form.show', $this->survey))
            ->assertRedirect(route('survey-form.index'));

        $this->assertDatabaseCount('survey_starts', 0);
    }
}
