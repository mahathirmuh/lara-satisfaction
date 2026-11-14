<?php

namespace Modules\SurveyManagement\Repositories;

use App\Enums\SurveyStatus;
use App\Helpers\Apps;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyQuestion;
use App\Models\UserManagement\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SurveyRepository implements SurveyInterface
{
    /**
     * {@inheritDoc}
     */
    public function paginate(array $params, array $filters = []): LengthAwarePaginator
    {
        return Survey::query()
            ->withCount(['questions', 'responses'])
            ->when($params['search'], fn (Builder $query, string $search) => $query->where('title', 'like', Apps::likePattern($search)))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->orderBy($params['order_by'], $params['order_dir'])
            ->orderByDesc('id')
            ->paginate($params['per_page']);
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data, User $creator): Survey
    {
        return Survey::create([...$data, 'created_by' => $creator->getKey()]);
    }

    /**
     * {@inheritDoc}
     */
    public function update(Survey $survey, array $data): Survey
    {
        $survey->update($data);

        return $survey;
    }

    /**
     * {@inheritDoc}
     */
    public function changeStatus(Survey $survey, SurveyStatus $status): Survey
    {
        $survey->update(['status' => $status]);

        return $survey;
    }

    /**
     * {@inheritDoc}
     */
    public function duplicate(Survey $survey, User $creator): Survey
    {
        return DB::transaction(function () use ($survey, $creator): Survey {
            $copy = $survey->replicate(['created_by']);
            $copy->fill([
                'title' => Str::limit($survey->title.' (Copy)', 255, ''),
                'status' => SurveyStatus::Draft,
                'created_by' => $creator->getKey(),
            ]);
            $copy->save();

            $survey->questions->each(function (SurveyQuestion $question) use ($copy): void {
                $question->replicate()->fill(['survey_id' => $copy->getKey()])->save();
            });

            return $copy;
        });
    }

    /**
     * {@inheritDoc}
     */
    public function delete(Survey $survey): void
    {
        $survey->delete();
    }
}
