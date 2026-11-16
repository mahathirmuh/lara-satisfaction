<?php

namespace Modules\SurveyManagement\Http\Requests;

use App\Models\Survey\Survey;
use App\Models\Survey\SurveyManualReminder;
use App\Services\Survey\SurveyReminders;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Remind by hand, about an open survey, everyone who has not completed it
 * ("all") or chosen people ("custom", handy for a test send to oneself).
 */
class SendReminderRequest extends FormRequest
{
    /**
     * Most people chosen in one send; beyond that "all" is the way.
     */
    public const MAX_CHOSEN = 200;

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
            'survey_id' => ['required', 'integer', Rule::exists('surveys', 'id')],
            'audience' => ['required', Rule::in([SurveyManualReminder::AUDIENCE_ALL, SurveyManualReminder::AUDIENCE_CUSTOM])],
            'user_ids' => [
                Rule::requiredIf(fn (): bool => $this->input('audience') === SurveyManualReminder::AUDIENCE_CUSTOM),
                'nullable',
                'array',
                'max:'.self::MAX_CHOSEN,
            ],
            'user_ids.*' => ['integer', 'distinct', Rule::exists('users', 'id')->whereNull('deleted_at')],
        ];
    }

    /**
     * The survey must be open and someone must be left to remind.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $survey = Survey::query()->findOrFail((int) $this->input('survey_id'));

                if (! $survey->isOpen()) {
                    $validator->errors()->add('survey_id', __('Reminders can only be sent for a survey that is open.'));

                    return;
                }

                $hasSomeoneToRemind = app(SurveyReminders::class)->pendingFor($survey)
                    ->when($this->isCustom(), fn (Builder $query) => $query->whereKey($this->chosenUserIds()))
                    ->exists();

                if (! $hasSomeoneToRemind) {
                    $this->isCustom()
                        ? $validator->errors()->add('user_ids', __('None of the chosen people can be reminded: they completed the survey already, are inactive or do not fill in surveys.'))
                        : $validator->errors()->add('audience', __('Everyone has completed this survey already.'));
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
            'survey_id' => __('survey'),
            'audience' => __('recipients'),
            'user_ids' => __('people'),
            'user_ids.*' => __('person'),
        ];
    }

    /**
     * The survey to remind about.
     */
    public function survey(): Survey
    {
        return Survey::query()->findOrFail((int) $this->validated('survey_id'));
    }

    /**
     * The chosen people, or null to remind everyone who has not completed it.
     *
     * @return array<int, int>|null
     */
    public function recipientIds(): ?array
    {
        return $this->isCustom() ? $this->chosenUserIds() : null;
    }

    /**
     * Whether people were chosen one by one.
     */
    protected function isCustom(): bool
    {
        return $this->input('audience') === SurveyManualReminder::AUDIENCE_CUSTOM;
    }

    /**
     * @return array<int, int>
     */
    protected function chosenUserIds(): array
    {
        return array_values(array_unique(array_map('intval', (array) $this->input('user_ids', []))));
    }
}
