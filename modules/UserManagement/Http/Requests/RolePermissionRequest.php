<?php

namespace Modules\UserManagement\Http\Requests;

use App\Enums\DepartmentScope;
use App\Enums\PermissionAction;
use App\Helpers\JobTitles;
use App\Models\UserManagement\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Create / update a role with its menu access, CRUD matrix, the departments
 * whose survey results it sees and the job title rules that give it at
 * sign-in (one rule per line).
 */
class RolePermissionRequest extends FormRequest
{
    /**
     * Only an IT admin may change the IT Admin role.
     */
    public function authorize(): bool
    {
        $role = $this->route('role');

        return ! ($role instanceof Role && $role->isItAdmin() && ! $this->user()->isItAdmin());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($this->route('role'))],
            'home_page' => ['nullable', 'integer', Rule::exists('menu', 'id')->whereNotNull('route')],
            'job_titles' => ['nullable', 'string', 'max:2000'],
            'department_scope' => ['required', Rule::enum(DepartmentScope::class)],
            'menu_list' => ['nullable', 'array'],
            'menu_list.*' => ['integer', Rule::exists('menu', 'id')],
            'menu_permission' => ['nullable', 'array'],
            'menu_permission.*' => ['array'],
            'menu_permission.*.*' => ['integer', Rule::in(array_map(fn (PermissionAction $action): int => $action->value, PermissionAction::cases()))],
        ];
    }

    /**
     * The IT Admin role is only given by hand, never by a job title.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $role = $this->route('role');

                if ($role instanceof Role && $role->isItAdmin() && JobTitles::parse($this->input('job_titles')) !== []) {
                    $validator->errors()->add('job_titles', __('The IT Admin role is only given by hand, not by job title.'));
                }
            },
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('role name'),
            'home_page' => __('home page'),
            'job_titles' => __('job titles'),
            'department_scope' => __('survey results'),
        ];
    }

    /**
     * Role attributes ready to persist.
     *
     * @return array{name: string, home_page: int|null, job_titles: array<int, string>|null, department_scope: string}
     */
    public function roleAttributes(): array
    {
        return [
            'name' => $this->validated('name'),
            'home_page' => $this->validated('home_page'),
            'job_titles' => JobTitles::parse($this->validated('job_titles')) ?: null,
            'department_scope' => DepartmentScope::from($this->validated('department_scope'))->value,
        ];
    }
}
