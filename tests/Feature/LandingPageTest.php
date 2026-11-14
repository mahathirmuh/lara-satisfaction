<?php

namespace Tests\Feature;

use App\Models\Content\LandingContent;
use App\Models\Organization\Department;
use App\Models\Organization\DepartmentSection;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyQuestion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_introduces_the_open_survey_and_its_departments(): void
    {
        $department = Department::factory()->create(['name' => 'Supply Chain']);
        Department::factory()->create(['name' => 'Not Assessed This Time']);
        $survey = Survey::factory()->create(['title' => 'Operational Audit 2026', 'estimated_minutes' => 12]);
        SurveyQuestion::factory()->for($survey)->for($department)->create();

        $this->get(route('landing'))
            ->assertOk()
            ->assertSee('Your Voice Shapes Our Collective Future')
            ->assertSee('Welcome to the <strong class="text-reset">Operational Audit 2026</strong>.', false)
            ->assertSee('Supply Chain')
            ->assertSee('1 Key Department')
            ->assertSee('12 Minute Completion')
            ->assertSee(asset(LandingContent::DEFAULT_IMAGE))
            ->assertDontSee('Not Assessed This Time')
            ->assertSee(route('login'));
    }

    public function test_departments_show_their_active_sections_and_image(): void
    {
        $department = Department::factory()->create(['name' => 'Environmental', 'image_path' => 'departments/environment.webp']);
        DepartmentSection::factory()->for($department)->create(['name' => 'Sustainability', 'sort_order' => 1]);
        DepartmentSection::factory()->for($department)->create(['name' => 'Impact', 'sort_order' => 2]);
        DepartmentSection::factory()->for($department)->inactive()->create(['name' => 'Retired Section', 'sort_order' => 3]);
        Department::factory()->create(['name' => 'Supply Chain', 'image_path' => null]);

        $this->get(route('landing'))
            ->assertOk()
            ->assertSee('Sustainability &amp; Impact', false)
            ->assertDontSee('Retired Section')
            ->assertSee(asset('storage/departments/environment.webp'))
            ->assertSee('class="department-placeholder', false);
    }

    public function test_departments_without_any_image_use_compact_tiles(): void
    {
        Department::factory()->count(2)->create(['image_path' => null]);

        $this->get(route('landing'))
            ->assertOk()
            ->assertDontSee('class="department-placeholder', false);
    }

    public function test_landing_page_shows_the_content_saved_in_master_data(): void
    {
        LandingContent::factory()->withHeroImage('landing/plant.webp')->create([
            'hero_title' => 'Help Us Improve Site Services',
            'feedback_title' => 'Why We Ask',
            'feedback_body' => 'Answers are anonymous.',
            'closing_title' => 'Ready when you are',
            'closing_note' => null,
        ]);

        $this->get(route('landing'))
            ->assertOk()
            ->assertSeeInOrder(['Help Us Improve Site Services', 'Why We Ask', 'Answers are anonymous.', 'Ready when you are'])
            ->assertSee(asset('storage/landing/plant.webp'))
            ->assertDontSee('Your Voice Shapes Our Collective Future')
            ->assertDontSee('Authentication required via employee credentials.');
    }

    public function test_landing_page_without_an_open_survey_lists_active_departments(): void
    {
        Survey::factory()->closed()->create(['title' => 'Old Audit']);
        Department::factory()->create(['name' => 'Human Resources']);

        $this->get(route('landing'))
            ->assertOk()
            ->assertSee('There is no survey open at the moment')
            ->assertSee('Human Resources')
            ->assertDontSee('Old Audit');
    }

    public function test_survey_title_is_escaped(): void
    {
        $survey = Survey::factory()->create(['title' => 'Audit <script>alert("xss")</script>']);
        SurveyQuestion::factory()->for($survey)->create();

        $this->get(route('landing'))
            ->assertOk()
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert("xss")</script>', false);
    }

    public function test_editable_texts_are_escaped(): void
    {
        LandingContent::factory()->create([
            'hero_title' => 'Title <img src=x onerror=alert(1)>',
            'hero_intro' => 'Intro <b>bold</b> {survey}',
            'feedback_body' => 'Body <script>alert(2)</script>',
            'hero_image_alt' => '" onload="alert(3)',
        ]);
        $survey = Survey::factory()->create(['title' => 'Audit 2026']);
        SurveyQuestion::factory()->for($survey)->create();

        $this->get(route('landing'))
            ->assertOk()
            ->assertSee('Title &lt;img src=x onerror=alert(1)&gt;', false)
            ->assertSee('Intro &lt;b&gt;bold&lt;/b&gt; <strong class="text-reset">Audit 2026</strong>', false)
            ->assertSee('Body &lt;script&gt;alert(2)&lt;/script&gt;', false)
            ->assertDontSee('<img src=x onerror=alert(1)>', false)
            ->assertDontSee('<script>alert(2)</script>', false)
            ->assertDontSee('" onload="alert(3)', false);
    }

    public function test_signed_in_employee_is_pointed_to_my_survey(): void
    {
        $this->actingAs($this->employee())
            ->get(route('landing'))
            ->assertOk()
            ->assertSee('Go to My Survey')
            ->assertSee(route('survey-form.index'));
    }

    public function test_responses_carry_security_headers(): void
    {
        $this->get(route('landing'))
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }
}
