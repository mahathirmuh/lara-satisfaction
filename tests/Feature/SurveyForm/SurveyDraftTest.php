<?php

namespace Tests\Feature\SurveyForm;

use App\Enums\SurveyStatus;
use App\Models\Log\LogActivity;
use App\Models\Organization\Department;
use App\Models\Organization\DepartmentSection;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyDraft;
use App\Models\Survey\SurveyQuestion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\SurveyForm\Services\SurveySubmissionService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SurveyDraftTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_saves_unfinished_answers_as_an_encrypted_draft(): void
    {
        [$survey, $questions, $section] = $this->surveyWithSection();
        $employee = $this->employee();

        $this->actingAs($employee)
            ->postJson(route('survey-form.draft', $survey), [
                'evaluations' => [$section->id => [$questions['comment']->id => 'Unfinished thoughts about recruitment']],
                'step' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Draft saved. You can continue later from My Survey, also on another device.')
            ->assertJsonStructure(['saved_at', 'saved_label']);

        $draft = SurveyDraft::query()->sole();

        $this->assertSame($employee->id, $draft->user_id);
        $this->assertSame([$section->id => [$questions['comment']->id => 'Unfinished thoughts about recruitment']], $draft->payload['evaluations']);
        $this->assertSame(1, $draft->payload['step']);
        $this->assertStringNotContainsString('Unfinished thoughts', DB::table('survey_drafts')->value('payload'));
    }

    public function test_saving_again_replaces_the_previous_draft(): void
    {
        [$survey, $questions] = $this->surveyWithSection();
        $employee = $this->employee();

        $this->actingAs($employee)->postJson(route('survey-form.draft', $survey), ['answers' => [$questions['general']->id => 2]]);
        $this->actingAs($employee)->postJson(route('survey-form.draft', $survey), ['answers' => [$questions['general']->id => 5]])->assertOk();

        $this->assertSame([$questions['general']->id => 5], SurveyDraft::query()->sole()->payload['answers']);
    }

    public function test_wizard_restores_the_draft_of_the_employee(): void
    {
        [$survey, $questions, $section] = $this->surveyWithSection();
        $employee = $this->employee();
        SurveyDraft::factory()->for($survey)->for($employee)->create([
            'payload' => [
                'answers' => [$questions['general']->id => 4],
                'evaluations' => [$section->id => [$questions['rating']->id => 3]],
                'step' => 2,
            ],
        ]);

        $this->actingAs($employee)
            ->get(route('survey-form.show', $survey))
            ->assertOk()
            ->assertSee('Draft saved')
            ->assertViewHas('draft', fn (array $draft): bool => $draft['step'] === 2 && $draft['values'] === [
                'answers['.$questions['general']->id.']' => '4',
                'evaluations['.$section->id.']['.$questions['rating']->id.']' => '3',
            ]);
    }

    public function test_a_draft_is_only_visible_to_its_owner(): void
    {
        [$survey] = $this->surveyWithSection();
        SurveyDraft::factory()->for($survey)->for($this->employee())->create();

        $this->actingAs($this->employee())
            ->get(route('survey-form.show', $survey))
            ->assertOk()
            ->assertViewHas('draft', fn (?array $draft): bool => $draft === null);
    }

    public function test_submitting_deletes_the_draft(): void
    {
        [$survey, $questions, $section] = $this->surveyWithSection();
        $employee = $this->employee();
        $draft = SurveyDraft::factory()->for($survey)->for($employee)->create();

        app(SurveySubmissionService::class)->submit(
            $survey,
            $employee,
            [$questions['general']->id => 4],
            [$section->id => [$questions['rating']->id => 4]],
        );

        $this->assertModelMissing($draft);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function unavailableDrafts(): array
    {
        return [
            'closed survey' => ['closed'],
            'already submitted' => ['submitted'],
        ];
    }

    #[DataProvider('unavailableDrafts')]
    public function test_draft_cannot_be_saved_for_a_closed_or_submitted_survey(string $case): void
    {
        [$survey, $questions, $section] = $this->surveyWithSection();
        $employee = $this->employee();

        if ($case === 'closed') {
            $survey->update(['status' => SurveyStatus::Closed]);
        } else {
            app(SurveySubmissionService::class)->submit($survey, $employee, [$questions['general']->id => 4], [$section->id => [$questions['rating']->id => 4]]);
        }

        $this->actingAs($employee)
            ->postJson(route('survey-form.draft', $survey), ['answers' => [$questions['general']->id => 3]])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'This survey is closed or already submitted, so the draft cannot be saved.');

        $this->assertDatabaseCount('survey_drafts', 0);
    }

    public function test_draft_only_accepts_answers_to_this_survey(): void
    {
        [$survey] = $this->surveyWithSection();
        $foreignQuestion = SurveyQuestion::factory()->general()->create();

        $this->actingAs($this->employee())
            ->postJson(route('survey-form.draft', $survey), ['answers' => [$foreignQuestion->id => 5]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['answers' => 'The submitted answers do not match this survey.']);

        $this->assertDatabaseCount('survey_drafts', 0);
    }

    public function test_draft_answers_are_never_written_to_the_activity_log(): void
    {
        [$survey, $questions, $section] = $this->surveyWithSection();

        $this->actingAs($this->employee())->postJson(route('survey-form.draft', $survey), [
            'evaluations' => [$section->id => [$questions['comment']->id => 'Private draft remark']],
        ]);

        $log = LogActivity::query()->where('route_name', 'survey-form.draft')->sole();

        $this->assertStringNotContainsString('Private draft remark', $log->data);
    }

    public function test_my_survey_offers_to_continue_a_saved_draft(): void
    {
        [$survey] = $this->surveyWithSection();
        $employee = $this->employee();
        SurveyDraft::factory()->for($survey)->for($employee)->create();

        $this->actingAs($employee)
            ->get(route('survey-form.index'))
            ->assertOk()
            ->assertSee('In Progress')
            ->assertSee('Saved as draft')
            ->assertSee('Continue Survey');
    }

    public function test_drafts_of_surveys_that_are_no_longer_open_are_pruned(): void
    {
        [$openSurvey] = $this->surveyWithSection();
        $openDraft = SurveyDraft::factory()->for($openSurvey)->create();
        $closedDraft = SurveyDraft::factory()->for(Survey::factory()->closed())->create();
        $endedDraft = SurveyDraft::factory()->for(Survey::factory()->state(['start_date' => '2020-01-01', 'end_date' => '2020-01-31']))->create();

        $this->artisan('model:prune', ['--model' => [SurveyDraft::class]])->assertSuccessful();

        $this->assertModelExists($openDraft);
        $this->assertModelMissing($closedDraft);
        $this->assertModelMissing($endedDraft);
    }

    /**
     * An open survey with a general question and, for a department with one
     * section, a rating and an optional comment question.
     *
     * @return array{0: Survey, 1: array<string, SurveyQuestion>, 2: DepartmentSection}
     */
    protected function surveyWithSection(): array
    {
        $survey = Survey::factory()->create();
        $section = DepartmentSection::factory()->for(Department::factory())->create(['name' => 'Recruitment']);

        $questions = [
            'general' => SurveyQuestion::factory()->for($survey)->general()->create(['sort_order' => 9]),
            'rating' => SurveyQuestion::factory()->for($survey)->create(['department_id' => $section->department_id, 'sort_order' => 1]),
            'comment' => SurveyQuestion::factory()->for($survey)->text()->create(['department_id' => $section->department_id, 'sort_order' => 2]),
        ];

        return [$survey, $questions, $section];
    }
}
