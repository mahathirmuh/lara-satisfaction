<?php

namespace Modules\SurveyForm\Http\Requests;

use App\Models\Survey\SurveyQuestion;

/**
 * Validates a draft: the same structure as a submission (only the survey's
 * own questions and sections), but nothing is required yet.
 */
class SaveDraftRequest extends SubmitSurveyRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'step' => ['nullable', 'integer', 'min:0', 'max:255'],
        ];
    }

    /**
     * Rules of one unfinished answer.
     *
     * @return array<int, string>
     */
    protected function answerRules(SurveyQuestion $question): array
    {
        return $question->isRating()
            ? ['nullable', 'integer', 'between:1,5']
            : ['nullable', 'string', 'max:'.config('survey.comment_max_length', 500)];
    }
}
