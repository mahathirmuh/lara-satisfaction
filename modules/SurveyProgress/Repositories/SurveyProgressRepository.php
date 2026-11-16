<?php

namespace Modules\SurveyProgress\Repositories;

use App\Enums\ParticipationStatus;
use App\Enums\SurveyStatus;
use App\Helpers\Apps;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyDraft;
use App\Models\Survey\SurveyParticipation;
use App\Models\Survey\SurveyStart;
use App\Models\UserManagement\User;
use App\Services\Survey\SurveyReminders;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Where every person who takes surveys stands with a survey. Only the
 * filling status is read: answers stay anonymous and are never linked to a
 * person. The people are the same as for reminders (SurveyReminders): active
 * accounts whose role fills in surveys.
 */
class SurveyProgressRepository implements SurveyProgressInterface
{
    /**
     * Create a new repository instance.
     */
    public function __construct(protected SurveyReminders $reminders) {}

    /**
     * {@inheritDoc}
     */
    public function surveys(): Collection
    {
        return Survey::query()
            ->where('status', '!=', SurveyStatus::Draft->value)
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get(['id', 'title', 'start_date', 'end_date', 'status']);
    }

    /**
     * {@inheritDoc}
     */
    public function summary(Survey $survey): array
    {
        $eligible = $this->reminders->surveyTakers()->count();
        $completed = $this->withStatus($survey, ParticipationStatus::Completed)->count();
        $inProgress = $this->withStatus($survey, ParticipationStatus::InProgress)->count();
        $savedDraft = $this->withStatus($survey, ParticipationStatus::InProgress)
            ->whereHas('surveyDrafts', fn (Builder $query) => $query->whereBelongsTo($survey))
            ->count();

        return [
            'eligible' => $eligible,
            'not_started' => max(0, $eligible - $completed - $inProgress),
            'in_progress' => $inProgress,
            'saved_draft' => $savedDraft,
            'completed' => $completed,
            'completion_rate' => $eligible > 0 ? round($completed / $eligible * 100, 1) : null,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function paginate(Survey $survey, array $params, array $filters): LengthAwarePaginator
    {
        $status = ParticipationStatus::tryFrom((string) ($filters['status'] ?? ''));

        return ($status === null ? $this->reminders->surveyTakers() : $this->withStatus($survey, $status))
            ->with('company:id,code,name')
            ->addSelect([
                'completed_at' => SurveyParticipation::query()->select('submitted_at')
                    ->whereColumn('user_id', 'users.id')->where('survey_id', $survey->id)->limit(1),
                'draft_saved_at' => SurveyDraft::query()->select('updated_at')
                    ->whereColumn('user_id', 'users.id')->where('survey_id', $survey->id)->limit(1),
                'started_at' => SurveyStart::query()->select('started_at')
                    ->whereColumn('user_id', 'users.id')->where('survey_id', $survey->id)->limit(1),
            ])
            ->when($filters['company'] ?? [], fn (Builder $query, array $ids) => $query->whereIn('company_id', $ids))
            ->when($params['search'], fn (Builder $query, string $search) => $query->where(
                fn (Builder $query) => $query
                    ->where('name', 'like', Apps::likePattern($search))
                    ->orWhere('email', 'like', Apps::likePattern($search))
                    ->orWhere('employee_id', 'like', Apps::likePattern($search))
                    ->orWhere('job_title', 'like', Apps::likePattern($search))
            ))
            ->orderBy($params['order_by'], $params['order_dir'])
            ->orderBy('id')
            ->paginate($params['per_page']);
    }

    /**
     * People who take surveys and stand at the given status.
     *
     * @return Builder<User>
     */
    protected function withStatus(Survey $survey, ParticipationStatus $status): Builder
    {
        $ofSurvey = fn (Builder $query) => $query->whereBelongsTo($survey);
        $people = $this->reminders->surveyTakers();

        return match ($status) {
            ParticipationStatus::Completed => $people->whereHas('participations', $ofSurvey),
            ParticipationStatus::InProgress => $people
                ->whereDoesntHave('participations', $ofSurvey)
                ->where(fn (Builder $query) => $query
                    ->whereHas('surveyDrafts', $ofSurvey)
                    ->orWhereHas('surveyStarts', $ofSurvey)),
            ParticipationStatus::NotStarted => $people
                ->whereDoesntHave('participations', $ofSurvey)
                ->whereDoesntHave('surveyDrafts', $ofSurvey)
                ->whereDoesntHave('surveyStarts', $ofSurvey),
        };
    }
}
