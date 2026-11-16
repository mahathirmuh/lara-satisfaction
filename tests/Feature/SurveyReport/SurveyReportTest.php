<?php

namespace Tests\Feature\SurveyReport;

use App\Models\Organization\Company;
use App\Models\Organization\Department;
use App\Models\Organization\DepartmentSection;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyQuestion;
use App\Models\UserManagement\Role;
use App\Models\UserManagement\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SurveyForm\Services\SurveySubmissionService;
use Tests\TestCase;

class SurveyReportTest extends TestCase
{
    use RefreshDatabase;

    protected Survey $survey;

    /**
     * @var array<string, SurveyQuestion>
     */
    protected array $questions;

    protected Company $mainCompany;

    protected Company $smallCompany;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAccessControl();

        $humanResources = Department::factory()->create(['code' => 'HR', 'name' => 'Human Resources', 'sort_order' => 1]);
        $supplyChain = Department::factory()->create(['code' => 'SCM', 'name' => 'Supply Chain', 'sort_order' => 2]);
        [$this->survey, $this->questions] = $this->openSurveyFor([$humanResources, $supplyChain]);

        $this->mainCompany = Company::factory()->create(['name' => 'PT Merdeka Battery Materials']);
        $this->smallCompany = Company::factory()->create(['name' => 'PT Small Site']);
    }

    public function test_scores_are_aggregated_per_department_and_overall(): void
    {
        $this->seedResponses();

        $this->actingAs($this->superAdmin())
            ->get(route('survey-report.show', $this->survey))
            ->assertOk()
            ->assertViewHas('summary', fn (array $summary): bool => $summary['responses'] === 8
                && $summary['is_reportable']
                && $summary['average'] === 3.75
                && $summary['csat'] === 75.0)
            ->assertViewHas('departmentScores', fn ($scores): bool => $scores->pluck('average', 'name')->all() === [
                'Human Resources' => 3.5,
                'Supply Chain' => 4.0,
            ]);
    }

    public function test_each_section_is_scored_from_its_own_evaluations(): void
    {
        $humanResources = $this->questions['HR']->department;
        $recruitment = DepartmentSection::factory()->for($humanResources)->create(['name' => 'Recruitment', 'sort_order' => 1]);
        $benefits = DepartmentSection::factory()->for($humanResources)->create(['name' => 'Benefits', 'sort_order' => 2]);

        foreach ([5, 5, 4, 2, 4] as $rating) {
            $this->submitEvaluations([$recruitment->id => 3, $benefits->id => $rating]);
        }

        $this->actingAs($this->superAdmin())
            ->get(route('survey-report.show', $this->survey))
            ->assertOk()
            ->assertViewHas('sectionScores', fn ($scores): bool => $scores->pluck('name')->all() === ['Recruitment', 'Benefits']
                && $scores[1]['department_id'] === $humanResources->id
                && $scores[1]['evaluations'] === 5
                && ! $scores[1]['is_hidden']
                && $scores[1]['average'] === 4.0
                && $scores[1]['csat'] === 80.0
                && $scores[0]['average'] === 3.0)
            ->assertViewHas('departmentScores', fn ($scores): bool => $scores->firstWhere('name', 'Human Resources')['average'] === 3.5)
            ->assertSeeInOrder(['Department &amp; Section Scores', 'Human Resources', 'Recruitment', 'Benefits', 'Supply Chain'], false);
    }

    public function test_sections_evaluated_by_fewer_employees_than_the_threshold_hide_their_scores(): void
    {
        $humanResources = $this->questions['HR']->department;
        $benefits = DepartmentSection::factory()->for($humanResources)->create(['name' => 'Benefits']);

        foreach ([4, 4, 4, 4, 4] as $rating) {
            $this->submitEvaluations([$benefits->id => $rating]);
        }

        // A section added while the survey is running is only evaluated by the later respondents.
        $payroll = DepartmentSection::factory()->for($humanResources)->create(['name' => 'Payroll']);
        $this->submitEvaluations([$benefits->id => 4, $payroll->id => 1]);
        $this->submitEvaluations([$benefits->id => 4, $payroll->id => 1]);

        $this->actingAs($this->superAdmin())
            ->get(route('survey-report.show', $this->survey))
            ->assertOk()
            ->assertViewHas('sectionScores', function ($scores): bool {
                $payrollRow = $scores->firstWhere('name', 'Payroll');

                return $payrollRow['evaluations'] === 2
                    && $payrollRow['is_hidden']
                    && $payrollRow['average'] === null
                    && $payrollRow['criticality'] === null
                    && array_sum($payrollRow['distribution']) === 0;
            })
            ->assertViewHas('departmentScores', fn ($scores): bool => $scores->firstWhere('name', 'Human Resources')['average'] === round(30 / 9, 2))
            ->assertSee('Hidden: fewer than 5 employees evaluated this section.');
    }

    public function test_criticality_is_reported_apart_from_the_satisfaction_scores(): void
    {
        $criticality = SurveyQuestion::factory()->for($this->survey)->for($this->questions['HR']->department)->criticality()->create();
        $service = app(SurveySubmissionService::class);

        for ($index = 0; $index < 5; $index++) {
            $service->submit($this->survey, $this->respondent($this->mainCompany), [
                $this->questions['HR']->id => 2,
                $this->questions['SCM']->id => 2,
                $criticality->id => 5,
            ]);
        }

        $this->actingAs($this->superAdmin())
            ->get(route('survey-report.show', $this->survey))
            ->assertOk()
            ->assertViewHas('summary', fn (array $summary): bool => $summary['average'] === 2.0
                && $summary['csat'] === 0.0
                && $summary['ratings'] === 10
                && $summary['criticality'] === 5.0)
            ->assertViewHas('departmentScores', fn ($scores): bool => $scores->firstWhere('name', 'Human Resources')['average'] === 2.0
                && $scores->firstWhere('name', 'Human Resources')['criticality'] === 5.0);
    }

    public function test_export_has_a_column_per_question_and_section(): void
    {
        $humanResources = $this->questions['HR']->department;
        $recruitment = DepartmentSection::factory()->for($humanResources)->create(['name' => 'Recruitment', 'sort_order' => 1]);
        $benefits = DepartmentSection::factory()->for($humanResources)->create(['name' => 'Benefits', 'sort_order' => 2]);
        $this->questions['HR']->update(['question' => 'How would you rate {section}?']);

        foreach ([4, 4, 4, 4, 4] as $rating) {
            $this->submitEvaluations([$recruitment->id => $rating, $benefits->id => 2]);
        }

        $content = $this->actingAs($this->superAdmin())
            ->get(route('survey-report.export', $this->survey))
            ->streamedContent();

        $rows = array_map('str_getcsv', array_filter(explode("\n", trim(substr($content, 3)))));

        $this->assertSame([
            'Q1 [Human Resources / Recruitment] How would you rate Recruitment?',
            'Q1 [Human Resources / Benefits] How would you rate Benefits?',
        ], array_slice($rows[0], 2, 2));
        $this->assertSame(['4', '2'], array_slice($rows[1], 2, 2));
    }

    public function test_participation_rate_compares_completions_with_active_employees(): void
    {
        $this->seedResponses();
        User::factory()->count(2)->create(['company_id' => $this->mainCompany->id]);
        User::factory()->inactive()->create(['company_id' => $this->mainCompany->id]);

        $this->actingAs($admin = $this->superAdmin())
            ->get(route('survey-report.show', $this->survey))
            ->assertViewHas('summary', fn (array $summary): bool => $summary['participants'] === 8
                && $summary['eligible'] === 11
                && $summary['participation_rate'] === round(8 / 11 * 100, 1));

        $this->assertTrue($admin->is_active);
    }

    public function test_companies_below_the_anonymity_threshold_are_merged(): void
    {
        $this->seedResponses();

        $this->actingAs($this->superAdmin())
            ->get(route('survey-report.show', $this->survey))
            ->assertViewHas('companyBreakdown', fn ($rows): bool => $rows->count() === 2
                && $rows[0]['company'] === 'PT Merdeka Battery Materials'
                && $rows[0]['responses'] === 6
                && str_starts_with($rows[1]['company'], 'Other companies')
                && $rows[1]['responses'] === 2
                && $rows[1]['average'] === null)
            ->assertDontSee('PT Small Site</td>', false);
    }

    public function test_results_stay_hidden_until_the_anonymity_threshold_is_reached(): void
    {
        $this->submit($this->mainCompany, 3, 4, 4, 'Too few to show');
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->get(route('survey-report.show', $this->survey))
            ->assertOk()
            ->assertSee('Results hidden to protect anonymity')
            ->assertViewHas('summary', fn (array $summary): bool => ! $summary['is_reportable'] && $summary['average'] === null)
            ->assertDontSee('Too few to show');

        $this->actingAs($admin)->getJson(route('survey-report.comments', $this->survey))->assertForbidden();

        $this->actingAs($admin)
            ->get(route('survey-report.export', $this->survey))
            ->assertRedirect(route('survey-report.show', $this->survey))
            ->assertSessionHas('alert.title', 'Export unavailable');
    }

    public function test_company_scoped_role_only_sees_its_companies(): void
    {
        $this->seedResponses();
        $viewer = $this->reportViewerFor([$this->mainCompany->id]);

        $this->actingAs($viewer)
            ->get(route('survey-report.show', $this->survey))
            ->assertOk()
            ->assertViewHas('summary', fn (array $summary): bool => $summary['responses'] === 6 && $summary['average'] === 4.5)
            ->assertViewHas('companyBreakdown', fn ($rows): bool => $rows->isEmpty());

        $this->actingAs($viewer)
            ->get(route('survey-report.show', ['survey' => $this->survey, 'company_id' => $this->smallCompany->id]))
            ->assertForbidden();
    }

    public function test_comments_can_be_filtered_by_department(): void
    {
        $this->seedResponses();
        $otherDepartmentComment = SurveyQuestion::factory()->for($this->survey)->text()->general()->create();
        app(SurveySubmissionService::class)->submit($this->survey, $this->respondent($this->mainCompany), [
            $this->questions['HR']->id => 3,
            $this->questions['SCM']->id => 3,
            $otherDepartmentComment->id => 'General remark',
        ]);

        $this->actingAs($this->superAdmin())
            ->getJson(route('survey-report.comments', ['survey' => $this->survey, 'department_id' => 'general']))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.comment', 'General remark')
            ->assertJsonPath('data.0.department', 'General');
    }

    public function test_export_contains_one_anonymous_row_per_response(): void
    {
        $this->seedResponses();

        $response = $this->actingAs($this->superAdmin())->get(route('survey-report.export', $this->survey));

        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $rows = array_map('str_getcsv', array_filter(explode("\n", trim(substr($response->streamedContent(), 3)))));

        $this->assertSame(['No', 'Company'], array_slice($rows[0], 0, 2));
        $this->assertStringStartsWith('Q1 [Human Resources]', $rows[0][2]);
        $this->assertCount(9, $rows);
        $this->assertSame(6, collect($rows)->where(1, 'PT Merdeka Battery Materials')->count());
        $this->assertSame(2, collect($rows)->where(1, 'Other')->count());
        $this->assertStringNotContainsString('@', implode(',', array_merge(...$rows)));
    }

    public function test_export_neutralises_spreadsheet_formulas_in_comments(): void
    {
        $this->seedResponses();
        $this->submit($this->mainCompany, 1, 5, 5, '=HYPERLINK("http://evil.test","click")');

        $content = $this->actingAs($this->superAdmin())
            ->get(route('survey-report.export', $this->survey))
            ->streamedContent();

        $this->assertStringContainsString("'=HYPERLINK", $content);
        $this->assertStringNotContainsString(',=HYPERLINK', $content);
    }

    public function test_draft_surveys_have_no_report(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('survey-report.show', Survey::factory()->draft()->create()))
            ->assertNotFound();
    }

    public function test_report_list_counts_responses_inside_the_role_scope(): void
    {
        $this->seedResponses();

        $this->actingAs($this->reportViewerFor([$this->smallCompany->id]))
            ->get(route('survey-report.index'))
            ->assertOk()
            ->assertViewHas('surveys', fn ($surveys): bool => $surveys->sole()->responses_count === 2);
    }

    public function test_manager_only_sees_the_departments_their_job_title_manages(): void
    {
        $this->seedResponses();
        $this->questions['SCM']->department->update(['manager_job_titles' => ['Supply Chain*Manager']]);
        $manager = $this->manager(['job_title' => 'Supply Chain Management Manager']);

        $this->actingAs($manager)
            ->get(route('survey-report.show', $this->survey))
            ->assertOk()
            ->assertViewHas('summary', fn (array $summary): bool => $summary['average'] === 4.0 && $summary['comments'] === 0)
            ->assertViewHas('departmentScores', fn ($scores): bool => $scores->pluck('name')->all() === ['Supply Chain'])
            ->assertViewHas('questionBreakdown', fn ($questions): bool => $questions->pluck('number', 'department')->all() === ['Supply Chain' => 3])
            ->assertViewHas('departments', fn ($departments): bool => $departments->pluck('name')->all() === ['Supply Chain'])
            ->assertDontSee('Keep it up');

        $this->actingAs($manager)
            ->getJson(route('survey-report.comments', $this->survey))
            ->assertOk()
            ->assertJsonPath('meta.total', 0);

        $rows = array_map('str_getcsv', array_filter(explode("\n", trim(substr(
            $this->actingAs($manager)->get(route('survey-report.export', $this->survey))->streamedContent(),
            3,
        )))));
        $this->assertCount(3, $rows[0]);
        $this->assertStringStartsWith('Q3 [Supply Chain]', $rows[0][2]);

        $this->actingAs($manager)
            ->get(route('dashboard.index'))
            ->assertViewHas('departmentScores', fn ($scores): bool => $scores->pluck('name')->all() === ['Supply Chain']);
    }

    public function test_manager_of_a_department_outside_the_survey_sees_no_results(): void
    {
        $this->seedResponses();
        $manager = $this->manager(['job_title' => 'Maintenance Manager']);

        $this->actingAs($manager)
            ->get(route('survey-report.index'))
            ->assertOk()
            ->assertSee('Your job title is not set as the manager of a surveyed department.');

        $this->actingAs($manager)->get(route('survey-report.show', $this->survey))->assertForbidden();
        $this->actingAs($manager)->get(route('survey-report.export', $this->survey))->assertForbidden();
        $this->actingAs($manager)->get(route('dashboard.index'))->assertViewHas('canViewReports', false);
    }

    /**
     * 6 responses from the main company (HR 4, SCM 5) and 2 from the small
     * company (HR 2, SCM 1): HR 3.5, SCM 4.0, overall 3.75, CSAT 75%.
     */
    protected function seedResponses(): void
    {
        $this->submit($this->mainCompany, 6, 4, 5, 'Keep it up');
        $this->submit($this->smallCompany, 2, 2, 1, null);
    }

    /**
     * Submit identical answers for new respondents of a company.
     */
    protected function submit(Company $company, int $count, int $humanResources, int $supplyChain, ?string $comment): void
    {
        $service = app(SurveySubmissionService::class);

        for ($index = 0; $index < $count; $index++) {
            $service->submit($this->survey, $this->respondent($company), array_filter([
                $this->questions['HR']->id => $humanResources,
                $this->questions['SCM']->id => $supplyChain,
                $this->questions['comment']->id => $comment,
            ]));
        }
    }

    /**
     * One response from the main company rating the quality of each given
     * Human Resources section (Supply Chain is rated 3).
     *
     * @param  array<int, int>  $ratingsBySection  Section id => quality rating
     */
    protected function submitEvaluations(array $ratingsBySection): void
    {
        app(SurveySubmissionService::class)->submit(
            $this->survey,
            $this->respondent($this->mainCompany),
            [$this->questions['SCM']->id => 3],
            collect($ratingsBySection)->map(fn (int $rating): array => [$this->questions['HR']->id => $rating])->all(),
        );
    }

    protected function respondent(Company $company): User
    {
        return User::factory()->create(['company_id' => $company->id]);
    }

    /**
     * A user whose role reads survey reports for the given companies only.
     *
     * @param  array<int, int>  $companyIds
     */
    protected function reportViewerFor(array $companyIds): User
    {
        $role = Role::query()->where('name', 'Super Admin')->sole();
        $role->update(['func_role' => 'COMPANY', 'setting_role' => $companyIds]);

        return User::factory()->withRole($role)->create(['company_id' => $companyIds[0]]);
    }
}
