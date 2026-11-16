<?php

namespace Modules\SurveyManagement\Http\Requests;

use App\Enums\QuestionType;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyQuestion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Create / update a survey question. Questions of a survey that already has
 * responses are frozen so earlier answers keep their meaning.
 */
class QuestionRequest extends FormRequest
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
            'is_required' => $this->boolean('is_required'),
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
            'survey_id' => ['required', 'integer', Rule::exists('surveys', 'id')],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
            'department_section_id' => [
                'bail',
                'nullable',
                'integer',
                'prohibited_if:department_id,null',
                Rule::exists('department_sections', 'id')->where('department_id', $this->integer('department_id')),
            ],
            'type' => ['required', Rule::enum(QuestionType::class)],
            'question' => ['required', 'string', 'max:1000'],
            'help_text' => ['nullable', 'string', 'max:500'],
            'is_required' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }

    /**
     * Get custom attributes for validator errors (named so they can be
     * translated; the English names are the ones Laravel derives).
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'department_section_id' => __('section'),
            'type' => __('type'),
            'question' => __('question'),
            'help_text' => __('help text'),
            'sort_order' => __('sort order'),
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
            'department_section_id.exists' => __('The section must belong to the selected department.'),
            'department_section_id.prohibited_if' => __('A general question cannot have a section.'),
        ];
    }

    /**
     * Reject changes to surveys that already collected responses.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('survey_id')) {
                    return;
                }

                $question = $this->route('question');
                $surveyIds = array_filter([
                    $this->integer('survey_id'),
                    $question instanceof SurveyQuestion ? $question->survey_id : null,
                ]);

                if (Survey::query()->whereKey($surveyIds)->whereHas('responses')->exists()) {
                    $validator->errors()->add('survey_id', __('This survey already has responses, so its questions can no longer be changed.'));
                }
            },
        ];
    }
}
