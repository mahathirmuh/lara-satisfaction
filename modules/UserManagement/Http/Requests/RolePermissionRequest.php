<?php

namespace Modules\UserManagement\Http\Requests;

use App\Enums\PermissionAction;
use App\Enums\RoleScope;
use App\Models\UserManagement\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create / update a role with its menu access, CRUD matrix and data scope.
 */
class RolePermissionRequest extends FormRequest
{
    /**
     * Only the super admin may change the super admin role.
     */
    public function authorize(): bool
    {
        $role = $this->route('role');

        return ! ($role instanceof Role && $role->isSuperAdmin() && ! $this->user()->isSuperAdmin());
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
            'menu_list' => ['nullable', 'array'],
            'menu_list.*' => ['integer', Rule::exists('menu', 'id')],
            'menu_permission' => ['nullable', 'array'],
            'menu_permission.*' => ['array'],
            'menu_permission.*.*' => ['integer', Rule::in(array_map(fn (PermissionAction $action): int => $action->value, PermissionAction::cases()))],
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
        ];
    }

    /**
     * Role attributes ready to persist.
     *
     * @return array{name: string, func_role: string, setting_role: array<int, int>|null, home_page: int|null}
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
        ];
    }
}
