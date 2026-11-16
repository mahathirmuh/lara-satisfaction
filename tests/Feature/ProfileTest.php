<?php

namespace Tests\Feature;

use App\Models\Organization\Company;
use App\Models\Organization\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_in_user_sees_what_the_directory_says_about_them(): void
    {
        $company = Company::factory()->create(['name' => 'PT Merdeka Battery Materials']);
        $employee = $this->employee([
            'name' => 'Budi Santoso',
            'email' => 'budi.santoso@merdekabattery.com',
            'employee_id' => 'MTI240001',
            'job_title' => 'IT Support Officer',
            'company_id' => $company->id,
        ]);

        $this->actingAs($employee)
            ->get(route('profile.show'))
            ->assertOk()
            ->assertSeeInOrder(['Budi Santoso', 'budi.santoso@merdekabattery.com', 'IT Support Officer'])
            ->assertSee('MTI240001')
            ->assertSee('PT Merdeka Battery Materials')
            ->assertSee('Employee')
            ->assertViewHas('surveysCompleted', 0)
            ->assertViewHas('seesAllResults', false)
            ->assertViewHas('resultDepartments', fn ($departments): bool => $departments->isEmpty());
    }

    public function test_manager_sees_the_departments_whose_results_they_can_see(): void
    {
        Department::factory()->create(['name' => 'Finance', 'manager_job_titles' => ['Finance Manager']]);
        Department::factory()->create(['name' => 'Interface', 'manager_job_titles' => ['Interface Manager']]);

        $this->actingAs($this->manager(['job_title' => 'Finance Manager']))
            ->get(route('profile.show'))
            ->assertOk()
            ->assertViewHas('resultDepartments', fn ($departments): bool => $departments->all() === ['Finance']);
    }

    public function test_super_admin_sees_the_results_of_every_department(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('profile.show'))
            ->assertOk()
            ->assertViewHas('seesAllResults', true)
            ->assertSee('All departments');
    }

    public function test_the_user_menu_links_to_the_profile(): void
    {
        $this->actingAs($this->employee())
            ->get(route('survey-form.index'))
            ->assertOk()
            ->assertSee(route('profile.show'));
    }

    public function test_guest_is_sent_to_the_sign_in_page(): void
    {
        $this->get(route('profile.show'))->assertRedirect(route('login'));
    }
}
