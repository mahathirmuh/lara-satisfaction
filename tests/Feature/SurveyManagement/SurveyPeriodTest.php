<?php

namespace Tests\Feature\SurveyManagement;

use App\Enums\SurveyStatus;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyQuestion;
use App\Models\UserManagement\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SurveyForm\Services\SurveySubmissionService;
use Tests\TestCase;

class SurveyPeriodTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->superAdmin();
    }

    public function test_list_returns_surveys_with_question_and_response_counts(): void
    {
        $survey = Survey::factory()->create(['title' => 'Operational Audit 2026']);
        SurveyQuestion::factory()->count(3)->for($survey)->create();

        $this->actingAs($this->admin)
            ->getJson(route('survey-management.survey.list'))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.title', 'Operational Audit 2026')
            ->assertJsonPath('data.0.questions_count', 3)
            ->assertJsonPath('data.0.responses_count', 0)
            ->assertJsonPath('data.0.is_open', true);
    }

    public function test_unknown_sort_columns_fall_back_to_the_default_order(): void
    {
        Survey::factory()->count(2)->create();

        $this->actingAs($this->admin)
            ->getJson(route('survey-management.survey.list', ['orderBy' => 'id; drop table users', 'orderDir' => 'sideways']))
            ->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_admin_creates_a_draft_survey(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('survey-management.survey.store'), [
                'title' => 'Operational Audit 2027',
                'description' => 'Next audit',
                'start_date' => '2027-01-01',
                'end_date' => '2027-01-31',
                'status' => 'draft',
                'estimated_minutes' => 20,
            ])
            ->assertCreated()
            ->assertJsonPath('message', 'Survey created successfully.');

        $survey = Survey::query()->sole();
        $this->assertSame(SurveyStatus::Draft, $survey->status);
        $this->assertSame($this->admin->id, $survey->created_by);
    }

    public function test_survey_period_must_end_after_it_starts(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('survey-management.survey.store'), [
                'title' => 'Broken period',
                'start_date' => '2027-02-01',
                'end_date' => '2027-01-01',
                'status' => 'draft',
                'estimated_minutes' => 15,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['end_date']);
    }

    public function test_survey_cannot_be_activated_without_questions(): void
    {
        $survey = Survey::factory()->draft()->create();

        $this->actingAs($this->admin)
            ->patchJson(route('survey-management.survey.status', $survey), ['status' => 'active'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status' => 'Add at least one question before activating the survey.']);

        $this->assertSame(SurveyStatus::Draft, $survey->fresh()->status);
    }

    public function test_survey_with_questions_can_be_published_and_closed(): void
    {
        $survey = Survey::factory()->draft()->create();
        SurveyQuestion::factory()->for($survey)->create();

        $this->actingAs($this->admin)
            ->patchJson(route('survey-management.survey.status', $survey), ['status' => 'active'])
            ->assertOk();
        $this->assertSame(SurveyStatus::Active, $survey->fresh()->status);

        $this->actingAs($this->admin)
            ->patchJson(route('survey-management.survey.status', $survey), ['status' => 'closed'])
            ->assertOk();
        $this->assertSame(SurveyStatus::Closed, $survey->fresh()->status);
    }

    public function test_duplicate_copies_the_questions_into_a_new_draft(): void
    {
        $survey = Survey::factory()->closed()->create(['title' => 'Operational Audit 2025']);
        SurveyQuestion::factory()->count(4)->for($survey)->create();

        $this->actingAs($this->admin)
            ->postJson(route('survey-management.survey.duplicate', $survey))
            ->assertCreated();

        $copy = Survey::query()->whereKeyNot($survey->id)->sole();
        $this->assertSame('Operational Audit 2025 (Copy)', $copy->title);
        $this->assertSame(SurveyStatus::Draft, $copy->status);
        $this->assertSame(4, $copy->questions()->count());
        $this->assertSame(4, $survey->questions()->count());
    }

    public function test_survey_with_responses_cannot_be_deleted(): void
    {
        $survey = Survey::factory()->create();
        $question = SurveyQuestion::factory()->for($survey)->create();
        app(SurveySubmissionService::class)->submit($survey, User::factory()->create(), [$question->id => 5]);

        $this->actingAs($this->admin)
            ->deleteJson(route('survey-management.survey.destroy', $survey))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'This survey already has responses and cannot be deleted. Close it instead.');

        $this->assertModelExists($survey);
    }

    public function test_survey_without_responses_can_be_deleted(): void
    {
        $survey = Survey::factory()->draft()->create();

        $this->actingAs($this->admin)
            ->deleteJson(route('survey-management.survey.destroy', $survey))
            ->assertOk();

        $this->assertModelMissing($survey);
    }
}
