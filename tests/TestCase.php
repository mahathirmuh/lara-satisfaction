<?php

namespace Tests;

use App\Enums\QuestionType;
use App\Models\Organization\Department;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyQuestion;
use App\Models\UserManagement\Role;
use App\Models\UserManagement\User;
use Database\Seeders\MenuSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Seed permissions, menus and the built-in roles with their RBAC matrix.
     */
    protected function seedAccessControl(): void
    {
        $this->seed([PermissionSeeder::class, MenuSeeder::class, RoleSeeder::class]);
    }

    /**
     * Create a user holding a built-in role.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function userWithRole(string $roleName, array $attributes = []): User
    {
        if (Role::query()->doesntExist()) {
            $this->seedAccessControl();
        }

        return User::factory()
            ->withRole(Role::query()->where('name', $roleName)->firstOrFail())
            ->create($attributes);
    }

    /**
     * The unrestricted role, only given by hand.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function itAdmin(array $attributes = []): User
    {
        return $this->userWithRole('IT Admin', $attributes);
    }

    /**
     * Survey administration and every result.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function superAdmin(array $attributes = []): User
    {
        return $this->userWithRole('Super Admin', $attributes);
    }

    /**
     * Fills in surveys; sees the results of the departments the job title manages.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function manager(array $attributes = []): User
    {
        return $this->userWithRole('Manager', $attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function employee(array $attributes = []): User
    {
        return $this->userWithRole('Employee', $attributes);
    }

    /**
     * An open survey with one required rating question per given department
     * and an optional text question for the first department.
     *
     * @param  array<int, Department>  $departments
     * @param  array<string, mixed>  $attributes
     * @return array{0: Survey, 1: array<string, SurveyQuestion>}
     */
    protected function openSurveyFor(array $departments, array $attributes = []): array
    {
        $survey = Survey::factory()->create($attributes);
        $questions = [];

        foreach ($departments as $index => $department) {
            $questions[$department->code] = SurveyQuestion::factory()->for($survey)->for($department)->create([
                'sort_order' => $index + 1,
            ]);
        }

        $questions['comment'] = SurveyQuestion::factory()->for($survey)->for($departments[0])->text()->create([
            'type' => QuestionType::Text,
            'sort_order' => 99,
        ]);

        return [$survey, $questions];
    }
}
