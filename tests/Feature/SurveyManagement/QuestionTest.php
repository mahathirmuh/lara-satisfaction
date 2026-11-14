<?php

namespace Tests\Feature\SurveyManagement;

use App\Models\Organization\Department;
use App\Models\Organization\DepartmentSection;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyQuestion;
use App\Models\UserManagement\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SurveyForm\Services\SurveySubmissionService;
use Tests\TestCase;

class QuestionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->surveyAdmin();
    }

    public function test_new_question_is_appended_after_the_existing_ones(): void
    {
        $survey = Survey::factory()->draft()->create();
        $department = Department::factory()->create();
        SurveyQuestion::factory()->for($survey)->create(['sort_order' => 7]);

        $this->actingAs($this->admin)
            ->postJson(route('survey-management.question.store'), [
                'survey_id' => $survey->id,
                'department_id' => $department->id,
                'type' => 'rating',
                'question' => 'How responsive is the team?',
                'is_required' => 1,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('survey_questions', [
            'survey_id' => $survey->id,
            'question' => 'How responsive is the team?',
            'sort_order' => 8,
            'is_required' => true,
        ]);
    }

    public function test_question_is_stored_in_a_section_of_its_department(): void
    {
        $survey = Survey::factory()->draft()->create();
        $section = DepartmentSection::factory()->create();

        $this->actingAs($this->admin)
            ->postJson(route('survey-management.question.store'), $this->sectionPayload($survey, $section->department_id, $section->id))
            ->assertCreated();

        $this->assertDatabaseHas('survey_questions', [
            'survey_id' => $survey->id,
            'department_id' => $section->department_id,
            'department_section_id' => $section->id,
        ]);
    }

    public function test_section_must_belong_to_the_selected_department(): void
    {
        $survey = Survey::factory()->draft()->create();
        $sectionOfAnotherDepartment = DepartmentSection::factory()->create();

        $this->actingAs($this->admin)
            ->postJson(route('survey-management.question.store'), $this->sectionPayload(
                $survey,
                Department::factory()->create()->id,
                $sectionOfAnotherDepartment->id,
            ))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['department_section_id' => 'The section must belong to the selected department.']);

        $this->assertDatabaseCount('survey_questions', 0);
    }

    public function test_general_question_cannot_have_a_section(): void
    {
        $survey = Survey::factory()->draft()->create();
        $section = DepartmentSection::factory()->create();

        $this->actingAs($this->admin)
            ->postJson(route('survey-management.question.store'), $this->sectionPayload($survey, null, $section->id))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['department_section_id' => 'A general question cannot have a section.'])
            ->assertJsonCount(1, 'errors.department_section_id');
    }

    public function test_list_filters_questions_of_a_section(): void
    {
        $survey = Survey::factory()->create();
        $section = DepartmentSection::factory()->create(['name' => 'Benefits']);
        SurveyQuestion::factory()->for($survey)->inSection($section)->create(['question' => 'Are benefit claims handled quickly?']);
        SurveyQuestion::factory()->for($survey)->for($section->department)->create();

        $this->actingAs($this->admin)
            ->getJson(route('survey-management.question.list', ['survey_id' => $survey->id, 'department_section_id' => $section->id]))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.question', 'Are benefit claims handled quickly?')
            ->assertJsonPath('data.0.section', 'Benefits');
    }

    public function test_list_filters_general_questions_of_a_survey(): void
    {
        $survey = Survey::factory()->create();
        SurveyQuestion::factory()->for($survey)->create();
        SurveyQuestion::factory()->for($survey)->general()->create(['question' => 'Overall satisfaction?']);
        SurveyQuestion::factory()->general()->create();

        $this->actingAs($this->admin)
            ->getJson(route('survey-management.question.list', ['survey_id' => $survey->id, 'department_id' => 'general']))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.question', 'Overall satisfaction?')
            ->assertJsonPath('data.0.department', 'General');
    }

    public function test_questions_of_a_survey_with_responses_are_locked(): void
    {
        $survey = Survey::factory()->create();
        $question = SurveyQuestion::factory()->for($survey)->create();
        app(SurveySubmissionService::class)->submit($survey, User::factory()->create(), [$question->id => 4]);

        $payload = [
            'survey_id' => $survey->id,
            'type' => 'rating',
            'question' => 'Changed meaning?',
            'is_required' => 1,
        ];

        $this->actingAs($this->admin)
            ->postJson(route('survey-management.question.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['survey_id']);

        $this->actingAs($this->admin)
            ->putJson(route('survey-management.question.update', $question), $payload)
            ->assertUnprocessable();

        $this->actingAs($this->admin)
            ->deleteJson(route('survey-management.question.destroy', $question))
            ->assertUnprocessable();

        $this->assertSame($question->question, $question->fresh()->question);
        $this->assertSame(1, $survey->questions()->count());
    }

    public function test_question_cannot_be_moved_into_a_locked_survey(): void
    {
        $lockedSurvey = Survey::factory()->create();
        $lockedQuestion = SurveyQuestion::factory()->for($lockedSurvey)->create();
        app(SurveySubmissionService::class)->submit($lockedSurvey, User::factory()->create(), [$lockedQuestion->id => 4]);
        $question = SurveyQuestion::factory()->for(Survey::factory()->draft())->create();

        $this->actingAs($this->admin)
            ->putJson(route('survey-management.question.update', $question), [
                'survey_id' => $lockedSurvey->id,
                'type' => 'rating',
                'question' => $question->question,
                'is_required' => 1,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['survey_id']);
    }

    /**
     * A valid rating question payload for a department and section.
     *
     * @return array<string, mixed>
     */
    protected function sectionPayload(Survey $survey, ?int $departmentId, ?int $sectionId): array
    {
        return [
            'survey_id' => $survey->id,
            'department_id' => $departmentId,
            'department_section_id' => $sectionId,
            'type' => 'rating',
            'question' => 'How clear are the benefit rules?',
            'is_required' => 1,
        ];
    }
}
