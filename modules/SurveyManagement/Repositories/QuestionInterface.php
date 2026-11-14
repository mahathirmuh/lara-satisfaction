<?php

namespace Modules\SurveyManagement\Repositories;

use App\Models\Survey\SurveyQuestion;
use Illuminate\Pagination\LengthAwarePaginator;

interface QuestionInterface
{
    /**
     * Paginated questions.
     *
     * @param  array{per_page: int, search: string|null, order_by: string, order_dir: string}  $params
     * @param  array{survey_id?: int|string|null, department_id?: int|string|null, department_section_id?: int|string|null, type?: string|null}  $filters
     * @return LengthAwarePaginator<int, SurveyQuestion>
     */
    public function paginate(array $params, array $filters = []): LengthAwarePaginator;

    /**
     * Create a question (appended to the end of the survey when no order is given).
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): SurveyQuestion;

    /**
     * Update a question.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(SurveyQuestion $question, array $data): SurveyQuestion;

    /**
     * Delete a question.
     */
    public function delete(SurveyQuestion $question): void;
}
