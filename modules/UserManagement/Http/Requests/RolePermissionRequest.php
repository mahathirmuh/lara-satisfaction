<?php

namespace Modules\UserManagement\Http\Requests;

use App\Enums\DepartmentScope;
use App\Enums\PermissionAction;
use App\Enums\RoleScope;
use App\Helpers\JobTitles;
use App\Models\UserManagement\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Create / update a role with its menu access, CRUD matrix, data scopes and
 * the job title rules that give it at sign-in (one rule per line).
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
            'func_role' => ['required', Rule::enum(RoleScope::class)],
            'setting_role' => ['nullable', 'array', Rule::requiredIf($this->input('func_role') === RoleScope::Company->value)],
            'setting_role.*' => ['integer', Rule::exists('companies', 'id')],
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
                    $validator->errors()->add('job_titles', 'The IT Admin role is only given by hand, not by job title.');
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
            'name' => 'role name',
            'func_role' => 'role type',
            'setting_role' => 'companies',
            'home_page' => 'home page',
            'job_titles' => 'job titles',
            'department_scope' => 'survey results',
        ];
    }

    /**
     * Role attributes ready to persist.
     *
     * @return array{name: string, func_role: string, setting_role: array<int, int>|null, home_page: int|null, job_titles: array<int, string>|null, department_scope: string}
     */
    public function roleAttributes(): array
    {
        $scope = RoleScope::from($this->validated('func_role'));

        return [
            'name' => $this->validated('name'),
            'func_role' => $scope->value,
            'setting_role' => $scope === RoleScope::Company
                ? array_values(array_unique(array_map('intval', $this->validated('setting_role') ?? [])))
                : null,
            'home_page' => $this->validated('home_page'),
            'job_titles' => JobTitles::parse($this->validated('job_titles')) ?: null,
            'department_scope' => DepartmentScope::from($this->validated('department_scope'))->value,
        ];
    }
}
