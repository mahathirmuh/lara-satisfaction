<?php

namespace Modules\SurveyForm\Http\Requests;

use App\Models\Organization\DepartmentSection;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyQuestion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Modules\SurveyForm\Repositories\SurveyFormInterface;

/**
 * Validates a submission against the survey's own steps. Every section of a
 * department is evaluated ("evaluations[section id][question id]");
 * questions of departments without sections and general questions are
 * answered once ("answers[question id]").
 */
class SubmitSurveyRequest extends FormRequest
{
    /**
     * Wizard steps of the survey (resolved once per request).
     *
     * @var Collection<int, array<string, mixed>>|null
     */
    protected ?Collection $steps = null;

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
        $rules = [];
        $answerKeys = [];
        $sectionKeys = [];

        foreach ($this->steps() as $step) {
            if ($step['sections']->isEmpty()) {
                foreach ($step['questions'] as $question) {
                    $answerKeys[] = $question->id;
                    $rules['answers.'.$question->id] = $this->answerRules($question);
                }

                continue;
            }

            foreach ($step['sections'] as $section) {
                $questions = $this->questionsFor($step, $section);
                $sectionKeys[] = $section->id;
                $rules['evaluations.'.$section->id] = ['array:'.$questions->pluck('id')->implode(',')];

                foreach ($questions as $question) {
                    $rules['evaluations.'.$section->id.'.'.$question->id] = $this->answerRules($question);
                }
            }
        }

        return [
            'answers' => ['nullable', $this->arrayWithKeys($answerKeys)],
            'evaluations' => ['nullable', $this->arrayWithKeys($sectionKeys)],
            ...$rules,
        ];
    }

    /**
     * Get custom attributes for validator errors, numbered like the wizard,
     * e.g. "question 2 of Asset Protection".
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = [];

        foreach ($this->steps() as $step) {
            if ($step['sections']->isEmpty()) {
                foreach ($step['questions']->values() as $index => $question) {
                    $attributes['answers.'.$question->id] = 'question '.($index + 1).' of '.($step['department']?->name ?? 'General');
                }

                continue;
            }

            foreach ($step['sections'] as $section) {
                foreach ($this->questionsFor($step, $section) as $index => $question) {
                    $attributes['evaluations.'.$section->id.'.'.$question->id] = 'question '.($index + 1).' of '.$section->name;
                }
            }
        }

        return $attributes;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'answers.array' => 'The submitted answers do not match this survey.',
            'evaluations.array' => 'The submitted answers do not match this survey.',
            'evaluations.*.array' => 'The submitted answers do not match this survey.',
        ];
    }

    /**
     * Questions answered for one section of a step, in display order.
     *
     * @param  array<string, mixed>  $step
     * @return Collection<int, SurveyQuestion>
     */
    protected function questionsFor(array $step, DepartmentSection $section): Collection
    {
        return $step['questions']->filter(fn (SurveyQuestion $question): bool => $question->appliesTo($section->id))->values();
    }

    /**
     * Rules of one answer.
     *
     * @return array<int, string>
     */
    protected function answerRules(SurveyQuestion $question): array
    {
        $presence = $question->is_required ? 'required' : 'nullable';

        return $question->isRating()
            ? [$presence, 'integer', 'between:1,5']
            : [$presence, 'string', 'max:'.config('survey.comment_max_length', 500)];
    }

    /**
     * "array" rule limited to the given keys.
     *
     * @param  array<int, int>  $keys
     */
    protected function arrayWithKeys(array $keys): string
    {
        return $keys === [] ? 'array' : 'array:'.implode(',', $keys);
    }

    /**
     * Wizard steps of the survey being answered.
     *
     * @return Collection<int, array<string, mixed>>
     */
    protected function steps(): Collection
    {
        /** @var Survey $survey */
        $survey = $this->route('survey');

        return $this->steps ??= app(SurveyFormInterface::class)->stepsFor($survey);
    }
}
