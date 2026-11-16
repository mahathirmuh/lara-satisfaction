<?php

namespace Modules\UserManagement\Http\Requests;

use App\Helpers\JobTitles;
use App\Models\UserManagement\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Register / update a user. Companies and roles are limited to what the
 * signed-in administrator may assign. There is no password: the user signs in
 * with SSO or the network account (LDAP) that has this email. The roles given
 * by job title follow the job title, so only the other roles are submitted.
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
        $allowedCompanyIds = $actor->scopedCompanyIds();

        $companyRule = Rule::exists('companies', 'id');

        if ($allowedCompanyIds !== null) {
            $companyRule->whereIn('id', $allowedCompanyIds === [] ? [0] : $allowedCompanyIds);
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($target)->withoutTrashed()],
            'employee_id' => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('users', 'employee_id')->ignore($target)->withoutTrashed()],
            'job_title' => ['nullable', 'string', 'max:255'],
            'company_id' => ['required', 'integer', $companyRule],
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
            'role_id.*.not_in' => 'Only an IT admin can assign the IT Admin role.',
            'employee_id.regex' => 'The employee ID may only contain letters, numbers, dots, dashes and underscores.',
        ];
    }
}
