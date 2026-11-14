<?php

namespace Modules\UserManagement\Http\Requests;

use App\Models\UserManagement\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Register / update a user. Companies and roles are limited to what the
 * signed-in administrator may assign.
 */
class UserRegisteredRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $target = $this->route('user');

        return ! ($target instanceof User && $target->isSuperAdmin() && ! $this->user()->isSuperAdmin());
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'employee_id' => filled($this->input('employee_id')) ? trim((string) $this->input('employee_id')) : null,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $target = $this->route('user');
        $actor = $this->user();
        $allowedCompanyIds = $actor->scopedCompanyIds();

        $companyRule = Rule::exists('companies', 'id');

        if ($allowedCompanyIds !== null) {
            $companyRule->whereIn('id', $allowedCompanyIds === [] ? [0] : $allowedCompanyIds);
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($target)->withoutTrashed()],
            'employee_id' => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('users', 'employee_id')->ignore($target)->withoutTrashed()],
            'company_id' => ['required', 'integer', $companyRule],
            'password' => ['nullable', 'string', 'max:255', Password::defaults()],
            'is_active' => ['required', 'boolean'],
            'role_id' => ['required', 'array', 'min:1'],
            'role_id.*' => [
                'integer',
                Rule::exists('roles', 'id'),
                Rule::when(! $actor->isSuperAdmin(), [Rule::notIn([(int) config('merdeka.super_admin_role_id')])]),
            ],
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
            'company_id' => 'company',
            'role_id' => 'role',
            'role_id.*' => 'role',
            'employee_id' => 'employee ID',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'role_id.*.not_in' => 'Only a super admin can assign the Super Admin role.',
            'employee_id.regex' => 'The employee ID may only contain letters, numbers, dots, dashes and underscores.',
        ];
    }
}
