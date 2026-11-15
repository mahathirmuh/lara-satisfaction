<?php

namespace Tests\Feature\Seeders;

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
            ['Supply Chain', 'Occupational Health & Safety', 'Environmental', 'External Affairs', 'Finance', 'Interface'],
            $steps->pluck('department.name')->all(),
        );
        foreach ($steps as $step) {
            $this->assertNotEmpty($step['sections'], $step['department']->name.' has no sections to evaluate.');
        }
    }
}
