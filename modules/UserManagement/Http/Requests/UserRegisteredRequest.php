<?php

namespace Modules\UserManagement\Http\Requests;

use App\Helpers\JobTitles;
use App\Models\UserManagement\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Register / update a user. Roles are limited to what the signed-in
 * administrator may assign. There is no password: the user signs in with SSO
 * or the network account (LDAP) that has this email. The roles given by job
 * title follow the job title, so only the other roles are submitted.
 */
class UserRegisteredRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $target = $this->route('user');

        return ! ($target instanceof User && $target->isItAdmin() && ! $this->user()->isItAdmin());
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'employee_id' => filled($this->input('employee_id')) ? trim((string) $this->input('employee_id')) : null,
            'job_title' => JobTitles::normalize($this->input('job_title')) ?: null,
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

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($target)->withoutTrashed()],
            'employee_id' => ['nullable', 'string', 'max:'.User::EMPLOYEE_ID_MAX_LENGTH, 'regex:'.User::EMPLOYEE_ID_PATTERN, Rule::unique('users', 'employee_id')->ignore($target)->withoutTrashed()],
            'job_title' => ['nullable', 'string', 'max:255'],
            'company_id' => ['required', 'integer', Rule::exists('companies', 'id')],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
            'is_active' => ['required', 'boolean'],
            'role_id' => ['nullable', 'array'],
            'role_id.*' => [
                'integer',
                Rule::exists('roles', 'id'),
                Rule::when(! $actor->isItAdmin(), [Rule::notIn([(int) config('merdeka.it_admin_role_id')])]),
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
            'company_id' => __('company'),
            'department_id' => __('department'),
            'role_id' => __('role'),
            'role_id.*' => __('role'),
            'employee_id' => __('employee ID'),
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
            'role_id.*.not_in' => __('Only an IT admin can assign the IT Admin role.'),
            'employee_id.regex' => __('The employee ID may only contain letters, numbers, dots, dashes and underscores.'),
        ];
    }
}
