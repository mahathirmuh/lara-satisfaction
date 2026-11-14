<?php

namespace Modules\SurveyManagement\Repositories;

use App\Enums\SurveyStatus;
use App\Models\Survey\Survey;
use App\Models\UserManagement\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface SurveyInterface
{
    /**
     * Paginated survey periods with question and response counts.
     *
     * @param  array{per_page: int, search: string|null, order_by: string, order_dir: string}  $params
     * @param  array{status?: string|null}  $filters
     * @return LengthAwarePaginator<int, Survey>
     */
    public function paginate(array $params, array $filters = []): LengthAwarePaginator;

    /**
     * Create a survey period.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $creator): Survey;

    /**
     * Update a survey period.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Survey $survey, array $data): Survey;

    /**
     * Change the publication status.
     */
    public function changeStatus(Survey $survey, SurveyStatus $status): Survey;

    /**
     * Copy a survey and its questions into a new draft.
     */
    public function duplicate(Survey $survey, User $creator): Survey;

    /**
     * Delete a survey that has no responses.
     */
    public function delete(Survey $survey): void;
}
