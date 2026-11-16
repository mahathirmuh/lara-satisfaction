<?php

namespace Tests\Feature\Seeders;

use App\Models\Organization\Department;
use App\Models\Organization\DepartmentSection;
use App\Models\Survey\Survey;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\SurveySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SurveyForm\Repositories\SurveyFormInterface;
use Tests\TestCase;

class DepartmentSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_survey_evaluates_the_support_services_departments_section_by_section(): void
    {
        $this->seed([DepartmentSeeder::class, SurveySeeder::class]);

        $steps = app(SurveyFormInterface::class)->stepsFor(Survey::query()->sole());

        $this->assertSame(
            ['Supply Chain', 'Occupational Health & Safety', 'Environmental', 'External Affairs', 'Finance', 'Interface', 'Human Resources'],
            $steps->pluck('department.name')->all(),
        );
        foreach ($steps as $step) {
            $this->assertNotEmpty($step['sections'], $step['department']->name.' has no sections to evaluate.');
        }
    }

    public function test_running_the_seeder_again_keeps_the_changes_made_in_master_data(): void
    {
        $this->seed(DepartmentSeeder::class);
        $section = DepartmentSection::query()->where('name', 'Plant OHS')->sole();
        $section->update(['name' => 'Plant Safety', 'description' => 'Edited in Master Data.']);
        $interface = Department::query()->where('code', 'INTF')->sole();
        $interface->update(['is_active' => false]);

        $this->seed(DepartmentSeeder::class);

        $this->assertDatabaseHas('department_sections', ['id' => $section->id, 'name' => 'Plant Safety', 'description' => 'Edited in Master Data.']);
        $this->assertDatabaseMissing('department_sections', ['name' => 'Plant OHS']);
        $this->assertFalse($interface->fresh()->is_active);
        $this->assertDatabaseCount('departments', 7);
    }
}
