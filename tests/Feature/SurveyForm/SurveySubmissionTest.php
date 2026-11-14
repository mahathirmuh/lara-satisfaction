<?php

namespace Tests\Feature\SurveyForm;

use App\Enums\SurveyStatus;
use App\Models\Log\LogActivity;
use App\Models\Organization\Company;
use App\Models\Organization\Department;
use App\Models\Organization\DepartmentSection;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyAnswer;
use App\Models\Survey\SurveyParticipation;
use App\Models\Survey\SurveyQuestion;
use App\Models\Survey\SurveyResponse;
use App\Models\UserManagement\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\SurveyForm\Exceptions\SurveySubmissionException;
use Modules\SurveyForm\Services\SurveySubmissionService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SurveySubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected Survey $survey;

    /**
     * @var array<string, SurveyQuestion>
     */
    protected array $questions;

    protected User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $humanResources = Department::factory()->create(['code' => 'HR', 'name' => 'Human Resources', 'sort_order' => 1]);
        $supplyChain = Department::factory()->create(['code' => 'SCM', 'name' => 'Supply Chain', 'sort_order' => 2]);

        [$this->survey, $this->questions] = $this->openSurveyFor([$humanResources, $supplyChain], ['title' => 'Operational Audit 2026']);
        $this->employee = $this->employee(['company_id' => Company::factory()->create()->id]);
    }

    public function test_employee_sees_the_open_survey_to_complete(): void
    {
        Survey::factory()->draft()->create(['title' => 'Unpublished Draft']);

        $this->actingAs($this->employee)
            ->get(route('survey-form.index'))
            ->assertOk()
            ->assertSee('Operational Audit 2026')
            ->assertSee('Not started')
            ->assertDontSee('Unpublished Draft');
    }

    public function test_my_survey_counts_down_the_last_days_before_the_deadline(): void
    {
        $this->survey->update(['end_date' => today()->addDays(5)]);
        Survey::factory()->create(['title' => 'Later Survey', 'end_date' => today()->addDays(30)]);

        $this->actingAs($this->employee)
            ->get(route('survey-form.index'))
            ->assertOk()
            ->assertSee('Closes in 5 days')
            ->assertSee('Later Survey')
            ->assertDontSee('Closes in 30 days');
    }

    public function test_wizard_starts_with_personal_data_then_one_step_per_department(): void
    {
        $this->actingAs($this->employee)
            ->get(route('survey-form.show', $this->survey))
            ->assertOk()
            ->assertSeeInOrder(['Personal Data', 'Human Resources', 'Supply Chain', 'Review &amp; Submit'], false)
            ->assertSeeInOrder([$this->employee->name, $this->employee->employee_id, $this->employee->company->name])
            ->assertSee($this->questions['HR']->question);
    }

    public function test_wizard_shows_the_overview_of_each_section_before_its_evaluation(): void
    {
        $humanResources = $this->questions['HR']->department;
        DepartmentSection::factory()->for($humanResources)->withOverview()->create([
            'name' => 'Employee Services',
            'description' => 'Handles payroll questions and benefits.',
            'team_caption' => 'Employee Services Team (Left to Right): Ani, Budi.',
        ]);
        $this->questions['HR']->update(['question' => 'How would you rate the service quality of {section} towards your department?']);

        $this->actingAs($this->employee)
            ->get(route('survey-form.show', $this->survey))
            ->assertOk()
            ->assertSeeInOrder([
                'Select Section to Evaluate',
                'Employee Services Overview',
                'Employee Services Team Picture', 'Employee Services Team (Left to Right): Ani, Budi.',
                'Employee Services Work Summary',
                'Role Overview', 'Handles payroll questions and benefits.',
                'Employee Services Evaluation',
            ])
            ->assertSee('How would you rate the service quality of <span class="js-section-name">Employee Services</span> towards your department?', false)
            ->assertSee(asset('storage/sections/team.webp'));
    }

    public function test_rating_questions_describe_every_level_of_their_scale(): void
    {
        SurveyQuestion::factory()->for($this->survey)->for($this->questions['HR']->department)->criticality()->create(['sort_order' => 2]);

        $this->actingAs($this->employee)
            ->get(route('survey-form.show', $this->survey))
            ->assertOk()
            ->assertSeeInOrder(['Rating Description - Service Quality Level:', '5 = <strong>Excellent:</strong> Service exceeds expectations consistently'], false)
            ->assertSeeInOrder(['Rating Description - Critical Level:', '1 = <strong>Not Critical:</strong> The service has little to no impact on my department'], false)
            ->assertSee('Feedback Guidelines:');
    }

    public function test_every_section_of_a_department_must_be_evaluated(): void
    {
        $humanResources = $this->questions['HR']->department;
        $recruitment = DepartmentSection::factory()->for($humanResources)->create(['name' => 'Recruitment', 'sort_order' => 1]);
        $training = DepartmentSection::factory()->for($humanResources)->create(['name' => 'Training', 'sort_order' => 2]);

        $this->actingAs($this->employee)
            ->post(route('survey-form.store', $this->survey), [
                'answers' => [$this->questions['SCM']->id => 3],
                'evaluations' => [$recruitment->id => [$this->questions['HR']->id => 4]],
            ])
            ->assertSessionHasErrors([
                'evaluations.'.$training->id.'.'.$this->questions['HR']->id => 'The question 1 of Training field is required.',
            ])
            ->assertSessionDoesntHaveErrors('evaluations.'.$recruitment->id.'.'.$this->questions['HR']->id);

        $this->assertSame(0, SurveyResponse::query()->count());
    }

    public function test_each_section_evaluation_is_stored_with_its_section(): void
    {
        $humanResources = $this->questions['HR']->department;
        $recruitment = DepartmentSection::factory()->for($humanResources)->create(['name' => 'Recruitment']);
        $training = DepartmentSection::factory()->for($humanResources)->create(['name' => 'Training']);

        $this->actingAs($this->employee)
            ->post(route('survey-form.store', $this->survey), [
                'answers' => [$this->questions['SCM']->id => 2],
                'evaluations' => [
                    $recruitment->id => [$this->questions['HR']->id => 5, $this->questions['comment']->id => 'Fast hiring'],
                    $training->id => [$this->questions['HR']->id => 3],
                ],
            ])
            ->assertRedirect(route('survey-form.thanks', $this->survey));

        $this->assertDatabaseHas('survey_answers', ['survey_question_id' => $this->questions['HR']->id, 'department_section_id' => $recruitment->id, 'rating' => 5]);
        $this->assertDatabaseHas('survey_answers', ['survey_question_id' => $this->questions['HR']->id, 'department_section_id' => $training->id, 'rating' => 3]);
        $this->assertDatabaseHas('survey_answers', ['survey_question_id' => $this->questions['comment']->id, 'department_section_id' => $recruitment->id, 'comment' => 'Fast hiring']);
        $this->assertDatabaseHas('survey_answers', ['survey_question_id' => $this->questions['SCM']->id, 'department_section_id' => null, 'rating' => 2]);
        $this->assertSame(4, SurveyAnswer::query()->count());
    }

    public function test_inactive_sections_are_not_evaluated(): void
    {
        $humanResources = $this->questions['HR']->department;
        $recruitment = DepartmentSection::factory()->for($humanResources)->create();
        $retired = DepartmentSection::factory()->for($humanResources)->inactive()->create();

        $this->actingAs($this->employee)
            ->post(route('survey-form.store', $this->survey), [
                'answers' => [$this->questions['SCM']->id => 3],
                'evaluations' => [$recruitment->id => [$this->questions['HR']->id => 4]],
            ])
            ->assertRedirect(route('survey-form.thanks', $this->survey));

        $this->assertDatabaseMissing('survey_answers', ['department_section_id' => $retired->id]);
    }

    public function test_evaluations_of_unknown_sections_are_rejected(): void
    {
        $humanResources = $this->questions['HR']->department;
        $recruitment = DepartmentSection::factory()->for($humanResources)->create();
        $otherDepartmentSection = DepartmentSection::factory()->for($this->questions['SCM']->department)->inactive()->create();

        $this->actingAs($this->employee)
            ->post(route('survey-form.store', $this->survey), [
                'answers' => [$this->questions['SCM']->id => 3],
                'evaluations' => [
                    $recruitment->id => [$this->questions['HR']->id => 4],
                    $otherDepartmentSection->id => [$this->questions['HR']->id => 1],
                ],
            ])
            ->assertSessionHasErrors(['evaluations' => 'The submitted answers do not match this survey.']);

        $this->assertSame(0, SurveyAnswer::query()->count());
    }

    public function test_question_of_one_section_is_only_asked_for_that_section(): void
    {
        $humanResources = $this->questions['HR']->department;
        $recruitment = DepartmentSection::factory()->for($humanResources)->create(['name' => 'Recruitment']);
        $training = DepartmentSection::factory()->for($humanResources)->create(['name' => 'Training']);
        $trainingQuestion = SurveyQuestion::factory()->for($this->survey)->inSection($training)->create(['sort_order' => 2]);

        $this->actingAs($this->employee)
            ->post(route('survey-form.store', $this->survey), [
                'answers' => [$this->questions['SCM']->id => 3],
                'evaluations' => [
                    $recruitment->id => [$this->questions['HR']->id => 4],
                    $training->id => [$this->questions['HR']->id => 4],
                ],
            ])
            ->assertSessionHasErrors(['evaluations.'.$training->id.'.'.$trainingQuestion->id => 'The question 2 of Training field is required.'])
            ->assertSessionDoesntHaveErrors('evaluations.'.$recruitment->id.'.'.$trainingQuestion->id);
    }

    public function test_answers_are_stored_without_any_link_to_the_respondent(): void
    {
        $this->actingAs($this->employee)
            ->post(route('survey-form.store', $this->survey), ['answers' => $this->validAnswers()])
            ->assertRedirect(route('survey-form.thanks', $this->survey));

        $this->assertDatabaseHas('survey_participations', [
            'survey_id' => $this->survey->id,
            'user_id' => $this->employee->id,
        ]);

        $response = SurveyResponse::query()->sole();
        $this->assertSame($this->employee->company_id, $response->company_id);
        $this->assertEqualsCanonicalizing(['id', 'survey_id', 'company_id'], Schema::getColumnListing('survey_responses'));
        $this->assertEqualsCanonicalizing(
            ['id', 'survey_response_id', 'survey_question_id', 'department_id', 'department_section_id', 'rating', 'comment'],
            Schema::getColumnListing('survey_answers')
        );

        $this->assertDatabaseHas('survey_answers', ['survey_question_id' => $this->questions['HR']->id, 'rating' => 4]);
        $this->assertDatabaseHas('survey_answers', ['survey_question_id' => $this->questions['SCM']->id, 'rating' => 2]);
        $this->assertDatabaseHas('survey_answers', [
            'survey_question_id' => $this->questions['comment']->id,
            'comment' => 'Great onboarding programme',
            'rating' => null,
        ]);
    }

    public function test_blank_optional_answers_are_not_stored(): void
    {
        $this->actingAs($this->employee)->post(route('survey-form.store', $this->survey), [
            'answers' => array_replace($this->validAnswers(), [$this->questions['comment']->id => '   ']),
        ])->assertRedirect(route('survey-form.thanks', $this->survey));

        $this->assertSame(2, SurveyAnswer::query()->count());
    }

    public function test_survey_can_only_be_completed_once(): void
    {
        $this->actingAs($this->employee)->post(route('survey-form.store', $this->survey), ['answers' => $this->validAnswers()]);

        $this->actingAs($this->employee)
            ->post(route('survey-form.store', $this->survey), ['answers' => $this->validAnswers()])
            ->assertRedirect(route('survey-form.index'))
            ->assertSessionHas('alert.title', 'Already submitted');

        $this->assertSame(1, SurveyResponse::query()->count());
        $this->assertSame(1, SurveyParticipation::query()->count());
    }

    public function test_database_constraint_rejects_a_second_submission_that_bypasses_the_page_checks(): void
    {
        $service = app(SurveySubmissionService::class);
        $service->submit($this->survey, $this->employee, $this->validAnswers());

        $this->expectExceptionObject(SurveySubmissionException::alreadySubmitted());

        try {
            $service->submit($this->survey, $this->employee, $this->validAnswers());
        } finally {
            $this->assertSame(1, SurveyResponse::query()->count());
        }
    }

    public function test_required_questions_must_be_answered(): void
    {
        $answers = $this->validAnswers();
        unset($answers[$this->questions['SCM']->id]);

        $this->actingAs($this->employee)
            ->post(route('survey-form.store', $this->survey), ['answers' => $answers])
            ->assertSessionHasErrors(['answers.'.$this->questions['SCM']->id => 'The question 1 of Supply Chain field is required.']);

        $this->assertSame(0, SurveyResponse::query()->count());
        $this->assertSame(0, SurveyParticipation::query()->count());
    }

    public function test_rating_must_be_between_one_and_five(): void
    {
        $this->actingAs($this->employee)
            ->post(route('survey-form.store', $this->survey), [
                'answers' => array_replace($this->validAnswers(), [$this->questions['HR']->id => 6]),
            ])
            ->assertSessionHasErrors(['answers.'.$this->questions['HR']->id => 'The question 1 of Human Resources field must be between 1 and 5.'])
            ->assertSessionDoesntHaveErrors('answers');

        $this->assertSame(0, SurveyResponse::query()->count());
    }

    public function test_answers_to_questions_of_another_survey_are_rejected(): void
    {
        $foreignQuestion = SurveyQuestion::factory()->create();

        $this->actingAs($this->employee)
            ->post(route('survey-form.store', $this->survey), [
                'answers' => array_replace($this->validAnswers(), [$foreignQuestion->id => 5]),
            ])
            ->assertSessionHasErrors(['answers' => 'The submitted answers do not match this survey.']);

        $this->assertSame(0, SurveyAnswer::query()->count());
    }

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function unavailableSurveys(): array
    {
        return [
            'draft' => [['status' => SurveyStatus::Draft]],
            'closed' => [['status' => SurveyStatus::Closed]],
            'period ended' => [['start_date' => '2020-01-01', 'end_date' => '2020-01-31']],
            'period not started' => [['start_date' => '2999-01-01', 'end_date' => '2999-01-31']],
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     */
    #[DataProvider('unavailableSurveys')]
    public function test_survey_outside_its_open_period_cannot_be_filled_in(array $state): void
    {
        $this->survey->update($state);

        $this->actingAs($this->employee)
            ->get(route('survey-form.show', $this->survey))
            ->assertRedirect(route('survey-form.index'));

        $this->actingAs($this->employee)
            ->post(route('survey-form.store', $this->survey), ['answers' => $this->validAnswers()])
            ->assertRedirect(route('survey-form.index'));

        $this->assertSame(0, SurveyResponse::query()->count());
    }

    public function test_submitted_answers_are_never_written_to_the_activity_log(): void
    {
        $this->actingAs($this->employee)->post(route('survey-form.store', $this->survey), ['answers' => $this->validAnswers()]);

        $log = LogActivity::query()->where('route_name', 'survey-form.store')->sole();

        $this->assertSame($this->employee->id, $log->user_id);
        $this->assertStringNotContainsString('Great onboarding programme', $log->data);
        $this->assertArrayNotHasKey('answers', json_decode($log->data, true));
    }

    public function test_thank_you_page_is_only_shown_after_completing_the_survey(): void
    {
        $this->actingAs($this->employee)
            ->get(route('survey-form.thanks', $this->survey))
            ->assertRedirect(route('survey-form.index'));

        $this->actingAs($this->employee)->post(route('survey-form.store', $this->survey), ['answers' => $this->validAnswers()]);

        $this->actingAs($this->employee)
            ->get(route('survey-form.thanks', $this->survey))
            ->assertOk()
            ->assertSee('Thank you for your feedback!');
    }

    public function test_response_and_answer_ids_are_random_and_do_not_reveal_submission_order(): void
    {
        $service = app(SurveySubmissionService::class);
        $responseIds = [];

        foreach (User::factory()->count(25)->create() as $respondent) {
            $responseIds[] = $service->submit($this->survey, $respondent, $this->validAnswers())->id;
        }

        foreach ([...$responseIds, ...SurveyAnswer::query()->pluck('id')->all()] as $id) {
            $this->assertTrue(Str::isUuid($id, 4), "Identifier {$id} is not a random version 4 UUID.");
        }

        $sorted = $responseIds;
        sort($sorted);

        $this->assertNotSame($responseIds, $sorted, 'Response ids sort in submission order.');
    }

    /**
     * Valid answers for the survey questions.
     *
     * @return array<int, int|string>
     */
    protected function validAnswers(): array
    {
        return [
            $this->questions['HR']->id => 4,
            $this->questions['SCM']->id => 2,
            $this->questions['comment']->id => 'Great onboarding programme',
        ];
    }
}
