<?php

namespace Modules\SurveyForm\Services;

use App\Models\Survey\Survey;
use App\Models\Survey\SurveyDraft;
use App\Models\Survey\SurveyParticipation;
use App\Models\Survey\SurveyQuestion;
use App\Models\Survey\SurveyResponse;
use App\Models\UserManagement\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Modules\SurveyForm\Exceptions\SurveySubmissionException;
use Modules\SurveyForm\Repositories\SurveyFormInterface;

/**
 * Stores a completed survey anonymously.
 *
 * Two independent records are written in one transaction:
 *  - SurveyParticipation: WHO completed the survey (no answers)
 *  - SurveyResponse + SurveyAnswer: WHAT was answered (no user, random UUIDs,
 *    no timestamps), with only the respondent's company kept for segmentation
 *
 * The unique (survey_id, user_id) index makes double submissions impossible
 * even for concurrent requests.
 */
class SurveySubmissionService
{
    /**
     * Create a new service instance.
     */
    public function __construct(protected SurveyFormInterface $surveyForm) {}

    /**
     * Submit the answers of a user.
     *
     * @param  array<int|string, mixed>  $answers  Question id => rating (1-5) or comment, for questions answered once
     * @param  array<int|string, mixed>  $evaluations  Section id => (question id => rating or comment), for every section of a department
     *
     * @throws SurveySubmissionException
     */
    public function submit(Survey $survey, User $user, array $answers, array $evaluations = []): SurveyResponse
    {
        if (! $survey->isOpen()) {
            throw SurveySubmissionException::notOpen();
        }

        try {
            return DB::transaction(function () use ($survey, $user, $answers, $evaluations): SurveyResponse {
                SurveyParticipation::create([
                    'survey_id' => $survey->getKey(),
                    'user_id' => $user->getKey(),
                    'submitted_at' => now(),
                ]);

                $response = SurveyResponse::create([
                    'survey_id' => $survey->getKey(),
                    'company_id' => $user->company_id,
                ]);

                $response->answers()->createMany($this->answerRows($survey, $answers, $evaluations));

                // The draft links the user to their answers, so it must not outlive the submission.
                SurveyDraft::query()->whereBelongsTo($survey)->whereBelongsTo($user)->delete();

                return $response;
            });
        } catch (UniqueConstraintViolationException) {
            throw SurveySubmissionException::alreadySubmitted();
        }
    }

    /**
     * Normalise the submitted values into answer rows following the survey
     * steps: department questions once per section of the department, other
     * questions once. Blanks and values that do not belong to the survey are
     * skipped.
     *
     * @param  array<int|string, mixed>  $answers
     * @param  array<int|string, mixed>  $evaluations
     * @return array<int, array{survey_question_id: int, department_id: int|null, department_section_id: int|null, rating: int|null, comment: string|null}>
     */
    protected function answerRows(Survey $survey, array $answers, array $evaluations): array
    {
        $rows = [];

        foreach ($this->surveyForm->stepsFor($survey) as $step) {
            if ($step['sections']->isEmpty()) {
                foreach ($step['questions'] as $question) {
                    $rows[] = $this->answerRow($question, null, $answers[$question->id] ?? null);
                }

                continue;
            }

            foreach ($step['sections'] as $section) {
                $sectionAnswers = is_array($evaluations[$section->id] ?? null) ? $evaluations[$section->id] : [];

                foreach ($step['questions'] as $question) {
                    if ($question->appliesTo($section->id)) {
                        $rows[] = $this->answerRow($question, $section->id, $sectionAnswers[$question->id] ?? null);
                    }
                }
            }
        }

        return array_values(array_filter($rows));
    }

    /**
     * One answer row, or null for a blank or invalid value.
     *
     * @return array{survey_question_id: int, department_id: int|null, department_section_id: int|null, rating: int|null, comment: string|null}|null
     */
    protected function answerRow(SurveyQuestion $question, ?int $sectionId, mixed $value): ?array
    {
        $row = [
            'survey_question_id' => $question->id,
            'department_id' => $question->department_id,
            'department_section_id' => $sectionId,
            'rating' => null,
            'comment' => null,
        ];

        if ($question->isRating()) {
            $rating = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]]);

            return $rating === false ? null : [...$row, 'rating' => $rating];
        }

        $comment = is_string($value) ? trim($value) : '';

        return $comment === ''
            ? null
            : [...$row, 'comment' => mb_substr($comment, 0, (int) config('survey.comment_max_length', 500))];
    }
}
