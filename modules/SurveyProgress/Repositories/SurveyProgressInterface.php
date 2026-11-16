<?php

namespace Modules\SurveyProgress\Repositories;

use App\Models\Survey\Survey;
use App\Models\UserManagement\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface SurveyProgressInterface
{
    /**
     * Surveys whose progress can be followed (every survey but drafts), the
     * latest first.
     *
     * @return Collection<int, Survey>
     */
    public function surveys(): Collection;

    /**
     * How many of the people who take surveys stand where.
     *
     * @return array{eligible: int, not_started: int, in_progress: int, saved_draft: int, completed: int, completion_rate: float|null}
     */
    public function summary(Survey $survey): array;

    /**
     * People who take surveys with when they started (started_at), saved a
     * draft (draft_saved_at) and completed (completed_at) the survey.
     *
     * @param  array{per_page: int, search: string|null, order_by: string, order_dir: string}  $params
     * @param  array{status?: string|null, company?: array<int, int>, department?: array<int, int>}  $filters
     * @return LengthAwarePaginator<int, User>
     */
    public function paginate(Survey $survey, array $params, array $filters): LengthAwarePaginator;
}
