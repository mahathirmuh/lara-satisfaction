<?php

namespace Modules\SurveyManagement\Repositories;

use App\Helpers\Apps;
use App\Models\Survey\SurveyQuestion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class QuestionRepository implements QuestionInterface
{
    /**
     * {@inheritDoc}
     */
    public function paginate(array $params, array $filters = []): LengthAwarePaginator
    {
        $surveyId = $filters['survey_id'] ?? null;
        $departmentId = $filters['department_id'] ?? null;
        $sectionId = $filters['department_section_id'] ?? null;
        $type = $filters['type'] ?? null;

        return SurveyQuestion::query()
            ->with(['survey' => fn ($query) => $query->withCount('responses'), 'department', 'section'])
            ->when(is_numeric($surveyId), fn (Builder $query) => $query->where('survey_id', (int) $surveyId))
            ->when($departmentId === 'general', fn (Builder $query) => $query->whereNull('department_id'))
            ->when(is_numeric($departmentId), fn (Builder $query) => $query->where('department_id', (int) $departmentId))
            ->when(is_numeric($sectionId), fn (Builder $query) => $query->where('department_section_id', (int) $sectionId))
            ->when(filled($type), fn (Builder $query) => $query->where('type', $type))
            ->when($params['search'], fn (Builder $query, string $search) => $query->where('question', 'like', Apps::likePattern($search)))
            ->orderBy($params['order_by'], $params['order_dir'])
            ->orderBy('id')
            ->paginate($params['per_page']);
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data): SurveyQuestion
    {
        $data['sort_order'] ??= (int) SurveyQuestion::query()->where('survey_id', $data['survey_id'])->max('sort_order') + 1;

        return SurveyQuestion::create($data);
    }

    /**
     * {@inheritDoc}
     */
    public function update(SurveyQuestion $question, array $data): SurveyQuestion
    {
        $data['sort_order'] ??= $question->sort_order;

        $question->update($data);

        return $question;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(SurveyQuestion $question): void
    {
        $question->delete();
    }
}
