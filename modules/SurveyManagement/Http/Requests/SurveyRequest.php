<?php

namespace Modules\SurveyManagement\Http\Requests;

use App\Enums\SurveyStatus;
use App\Models\Survey\Survey;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Create / update a survey period.
 */
class SurveyRequest extends FormRequest
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
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::enum(SurveyStatus::class)],
            'estimated_minutes' => ['required', 'integer', 'min:1', 'max:240'],
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
            'title' => __('title'),
            'description' => __('description'),
            'start_date' => __('start date'),
            'end_date' => __('end date'),
            'status' => __('status'),
            'estimated_minutes' => __('estimated minutes'),
        ];
    }

    /**
     * A survey can only be activated once it has questions.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('status') || $this->input('status') !== SurveyStatus::Active->value) {
                    return;
                }

                $survey = $this->route('survey');

                if (! $survey instanceof Survey || $survey->questions()->doesntExist()) {
                    $validator->errors()->add('status', __('Add at least one question before activating the survey.'));
                }
            },
        ];
    }
}
