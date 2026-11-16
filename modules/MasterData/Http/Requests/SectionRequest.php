<?php

namespace Modules\MasterData\Http\Requests;

use App\Helpers\FileApps;
use App\Models\Organization\DepartmentSection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Create / update a section of a department with the overview employees see
 * before evaluating it (role overview, team picture, work summary).
 */
class SectionRequest extends FormRequest
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
            'name' => trim((string) $this->input('name')),
            'is_active' => $this->boolean('is_active'),
            'remove_team_photo' => $this->boolean('remove_team_photo'),
            'remove_work_summary' => $this->boolean('remove_work_summary'),
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
            'department_id' => ['required', 'integer', Rule::exists('departments', 'id')],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('department_sections', 'name')
                    ->where('department_id', $this->integer('department_id'))
                    ->ignore($this->route('section')),
            ],
            'description' => ['nullable', 'string', 'max:3000'],
            'team_photo' => FileApps::imageRules(),
            'team_caption' => ['nullable', 'string', 'max:255'],
            'remove_team_photo' => ['boolean'],
            'work_summary' => FileApps::imageRules(),
            'work_summary_caption' => ['nullable', 'string', 'max:255'],
            'remove_work_summary' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * A section used in surveys cannot move to another department.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $section = $this->route('section');

                if (! $section instanceof DepartmentSection || $validator->errors()->has('department_id')) {
                    return;
                }

                if ($this->integer('department_id') !== $section->department_id && $section->isUsedInSurveys()) {
                    $validator->errors()->add('department_id', __('This section is used in surveys and cannot be moved to another department.'));
                }
            },
        ];
    }

    /**
     * Section attributes ready to persist (without the image fields).
     *
     * @return array<string, mixed>
     */
    public function sectionAttributes(): array
    {
        return $this->safe()->except([
            ...array_keys(DepartmentSection::IMAGES),
            'remove_team_photo',
            'remove_work_summary',
        ]);
    }

    /**
     * Uploaded images, keyed by form field.
     *
     * @return array<string, UploadedFile>
     */
    public function imageUploads(): array
    {
        return collect(array_keys(DepartmentSection::IMAGES))
            ->mapWithKeys(fn (string $field): array => [$field => $this->file($field)])
            ->filter(fn (mixed $file): bool => $file instanceof UploadedFile)
            ->all();
    }

    /**
     * Form fields whose stored image must be removed.
     *
     * @return array<int, string>
     */
    public function imageRemovals(): array
    {
        return array_values(array_filter(
            array_keys(DepartmentSection::IMAGES),
            fn (string $field): bool => $this->boolean('remove_'.$field),
        ));
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'description' => __('role overview'),
            'team_photo' => __('team picture'),
            'team_caption' => __('team picture caption'),
            'work_summary' => __('work summary'),
            'work_summary_caption' => __('work summary caption'),
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
            'name.unique' => __('This department already has a section with this name.'),
        ];
    }
}
