<?php

namespace Tests\Feature\MasterData;

use App\Models\Organization\Department;
use App\Models\Organization\DepartmentSection;
use App\Models\Survey\SurveyQuestion;
use App\Models\UserManagement\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\SurveyForm\Services\SurveySubmissionService;
use Tests\TestCase;

class SectionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->superAdmin();
    }

    public function test_new_section_is_appended_to_its_department(): void
    {
        $department = Department::factory()->create();
        DepartmentSection::factory()->for($department)->create(['sort_order' => 4]);
        DepartmentSection::factory()->create(['sort_order' => 9]);

        $this->actingAs($this->admin)
            ->postJson(route('master-data.section.store'), [
                'department_id' => $department->id,
                'name' => '  Benefits ',
                'is_active' => 1,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('department_sections', [
            'department_id' => $department->id,
            'name' => 'Benefits',
            'sort_order' => 5,
            'is_active' => true,
        ]);
    }

    public function test_section_overview_is_saved_with_its_pictures(): void
    {
        Storage::fake('public');
        $department = Department::factory()->create();

        $this->actingAs($this->admin)
            ->withHeader('Accept', 'application/json')
            ->post(route('master-data.section.store'), [
                'department_id' => $department->id,
                'name' => 'Asset Protection',
                'description' => 'Secures the assets of the company.',
                'team_photo' => UploadedFile::fake()->image('team.jpg', 1200, 800),
                'team_caption' => 'Asset Protection Team (Left to Right): Ani, Budi.',
                'work_summary' => UploadedFile::fake()->image('summary.png', 1200, 800),
                'work_summary_caption' => 'Work Summary Matrix Semester 1',
                'is_active' => 1,
            ])
            ->assertCreated();

        $section = DepartmentSection::query()->where('name', 'Asset Protection')->sole();

        $this->assertSame('Secures the assets of the company.', $section->description);
        $this->assertSame('Asset Protection Team (Left to Right): Ani, Budi.', $section->team_caption);
        $this->assertMatchesRegularExpression('#^sections/[A-Za-z0-9]{40}\.webp$#', $section->team_photo_path);
        $this->assertMatchesRegularExpression('#^sections/[A-Za-z0-9]{40}\.webp$#', $section->work_summary_path);
        Storage::disk('public')->assertExists([$section->team_photo_path, $section->work_summary_path]);

        $this->actingAs($this->admin)
            ->getJson(route('master-data.section.show', $section))
            ->assertOk()
            ->assertJsonPath('data.team_photo_url', asset('storage/'.$section->team_photo_path))
            ->assertJsonPath('data.work_summary_caption', 'Work Summary Matrix Semester 1');
    }

    public function test_removing_one_picture_keeps_the_other(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('sections/team.webp', 'image');
        Storage::disk('public')->put('sections/work-summary.webp', 'image');
        $section = DepartmentSection::factory()->withOverview()->create();

        $this->actingAs($this->admin)
            ->putJson(route('master-data.section.update', $section), [
                'department_id' => $section->department_id,
                'name' => $section->name,
                'remove_team_photo' => 1,
                'is_active' => 1,
            ])
            ->assertOk();

        $section->refresh();

        $this->assertNull($section->team_photo_path);
        $this->assertSame('sections/work-summary.webp', $section->work_summary_path);
        Storage::disk('public')->assertMissing('sections/team.webp');
        Storage::disk('public')->assertExists('sections/work-summary.webp');
    }

    public function test_deleting_a_section_deletes_its_pictures(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('sections/team.webp', 'image');
        Storage::disk('public')->put('sections/work-summary.webp', 'image');
        $section = DepartmentSection::factory()->withOverview()->create();

        $this->actingAs($this->admin)
            ->deleteJson(route('master-data.section.destroy', $section))
            ->assertOk();

        Storage::disk('public')->assertMissing(['sections/team.webp', 'sections/work-summary.webp']);
    }

    public function test_section_name_is_unique_within_its_department_only(): void
    {
        $humanResources = Department::factory()->create();
        DepartmentSection::factory()->for($humanResources)->create(['name' => 'Benefits']);

        $this->actingAs($this->admin)
            ->postJson(route('master-data.section.store'), [
                'department_id' => $humanResources->id,
                'name' => 'Benefits',
                'is_active' => 1,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name' => 'This department already has a section with this name.']);

        $this->actingAs($this->admin)
            ->postJson(route('master-data.section.store'), [
                'department_id' => Department::factory()->create()->id,
                'name' => 'Benefits',
                'is_active' => 1,
            ])
            ->assertCreated();
    }

    public function test_section_requires_an_existing_department(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('master-data.section.store'), ['department_id' => 999, 'name' => 'Orphan', 'is_active' => 1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['department_id' => 'The selected department id is invalid.']);
    }

    public function test_section_used_by_questions_cannot_move_to_another_department(): void
    {
        $section = DepartmentSection::factory()->create();
        SurveyQuestion::factory()->inSection($section)->create();

        $this->actingAs($this->admin)
            ->putJson(route('master-data.section.update', $section), [
                'department_id' => Department::factory()->create()->id,
                'name' => $section->name,
                'is_active' => 1,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'department_id' => 'This section is used in surveys and cannot be moved to another department.',
            ]);

        $this->assertSame($section->department_id, $section->fresh()->department_id);
    }

    public function test_evaluated_section_cannot_move_to_another_department(): void
    {
        $section = DepartmentSection::factory()->create();
        $this->evaluate($section);

        $this->actingAs($this->admin)
            ->putJson(route('master-data.section.update', $section), [
                'department_id' => Department::factory()->create()->id,
                'name' => $section->name,
                'is_active' => 1,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'department_id' => 'This section is used in surveys and cannot be moved to another department.',
            ]);

        $this->assertSame($section->department_id, $section->fresh()->department_id);
    }

    public function test_section_used_by_questions_can_still_be_renamed_and_deactivated(): void
    {
        $section = DepartmentSection::factory()->create(['sort_order' => 3]);
        SurveyQuestion::factory()->inSection($section)->create();

        $this->actingAs($this->admin)
            ->putJson(route('master-data.section.update', $section), [
                'department_id' => $section->department_id,
                'name' => 'Employee Benefits',
                'is_active' => 0,
            ])
            ->assertOk();

        $this->assertDatabaseHas('department_sections', [
            'id' => $section->id,
            'name' => 'Employee Benefits',
            'sort_order' => 3,
            'is_active' => false,
        ]);
    }

    public function test_section_used_by_questions_cannot_be_deleted(): void
    {
        $section = DepartmentSection::factory()->create();
        SurveyQuestion::factory()->inSection($section)->create();

        $this->actingAs($this->admin)
            ->deleteJson(route('master-data.section.destroy', $section))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'This section is used in surveys. Deactivate it instead of deleting it, so past results keep it.');

        $this->assertModelExists($section);
    }

    public function test_evaluated_section_cannot_be_deleted_so_past_answers_keep_it(): void
    {
        $section = DepartmentSection::factory()->create();
        $this->evaluate($section);

        $this->actingAs($this->admin)
            ->deleteJson(route('master-data.section.destroy', $section))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'This section is used in surveys. Deactivate it instead of deleting it, so past results keep it.');

        $this->assertModelExists($section);
        $this->assertDatabaseHas('survey_answers', ['department_section_id' => $section->id]);
    }

    public function test_list_marks_the_sections_used_in_surveys(): void
    {
        $evaluated = DepartmentSection::factory()->create(['name' => 'Evaluated', 'sort_order' => 1]);
        DepartmentSection::factory()->for($evaluated->department)->create(['name' => 'Unused', 'sort_order' => 2]);
        $this->evaluate($evaluated);

        $this->actingAs($this->admin)
            ->getJson(route('master-data.section.list', ['department_id' => $evaluated->department_id]))
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Evaluated')
            ->assertJsonPath('data.0.is_used_in_surveys', true)
            ->assertJsonPath('data.1.name', 'Unused')
            ->assertJsonPath('data.1.is_used_in_surveys', false);
    }

    public function test_unused_section_can_be_deleted(): void
    {
        $section = DepartmentSection::factory()->create();

        $this->actingAs($this->admin)
            ->deleteJson(route('master-data.section.destroy', $section))
            ->assertOk();

        $this->assertModelMissing($section);
    }

    public function test_list_filters_sections_by_department(): void
    {
        $environmental = Department::factory()->create(['name' => 'Environmental']);
        DepartmentSection::factory()->for($environmental)->create(['name' => 'Impact']);
        DepartmentSection::factory()->create(['name' => 'Benefits']);

        $this->actingAs($this->admin)
            ->getJson(route('master-data.section.list', ['department_id' => $environmental->id]))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.name', 'Impact')
            ->assertJsonPath('data.0.department', 'Environmental');
    }

    public function test_list_ignores_sort_columns_outside_the_allow_list(): void
    {
        DepartmentSection::factory()->count(2)->create();

        $this->actingAs($this->admin)
            ->getJson(route('master-data.section.list', ['orderBy' => 'name) desc; drop table users; --', 'orderDir' => 'sideways']))
            ->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    /**
     * An employee submits a survey that evaluates the section through a question
     * of its department (not a question about this section only).
     */
    protected function evaluate(DepartmentSection $section): void
    {
        $question = SurveyQuestion::factory()->for($section->department)->create();

        app(SurveySubmissionService::class)->submit($question->survey, $this->employee(), [], [
            $section->id => [$question->id => 4],
        ]);
    }
}
