<?php

namespace Modules\MasterData\Http\Requests;

use App\Helpers\FileApps;
use App\Helpers\JobTitles;
use App\Models\Organization\Department;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create / update an assessed department, including its illustration.
 */
class DepartmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request (RBAC is
     * enforced by the permission middleware).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtoupper(trim((string) $this->input('code'))),
            'is_active' => $this->boolean('is_active'),
            'remove_image' => $this->boolean('remove_image'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('departments', 'code')->ignore($this->route('department'))],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'icon' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9-]+$/'],
            'color' => ['required', Rule::in(Department::COLORS)],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['required', 'boolean'],
            'image' => FileApps::imageRules(),
            'remove_image' => ['boolean'],
            'manager_job_titles' => ['nullable', 'string', 'max:1000'],
            'directory_names' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Department attributes ready to persist (without the image fields), with
     * the manager job titles as rules and the directory department names as a
     * list (one per line each).
     *
     * @return array<string, mixed>
     */
    public function departmentAttributes(): array
    {
        return [
            ...$this->safe()->except(['image', 'remove_image']),
            'manager_job_titles' => JobTitles::parse($this->validated('manager_job_titles')) ?: null,
            'directory_names' => collect(preg_split('/\R/', (string) $this->validated('directory_names')))
                ->map(fn (string $name): string => (string) preg_replace('/\s+/', ' ', trim($name)))
                ->filter()
                ->unique(fn (string $name): string => mb_strtolower($name))
                ->values()
                ->all() ?: null,
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
            'code.regex' => __('The code may only contain letters, numbers, dashes and underscores.'),
            'icon.regex' => __('The icon must be a Tabler icon name, e.g. "leaf" or "users".'),
        ];
    }
}
