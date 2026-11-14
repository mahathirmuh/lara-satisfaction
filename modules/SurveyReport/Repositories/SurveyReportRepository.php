<?php

namespace Modules\SurveyReport\Repositories;

use App\Enums\QuestionType;
use App\Enums\SurveyStatus;
use App\Helpers\Apps;
use App\Models\Organization\Company;
use App\Models\Organization\Department;
use App\Models\Organization\DepartmentSection;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyAnswer;
use App\Models\Survey\SurveyParticipation;
use App\Models\Survey\SurveyQuestion;
use App\Models\Survey\SurveyResponse;
use App\Models\UserManagement\User;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Modules\SurveyReport\Support\ReportScope;

class SurveyReportRepository implements SurveyReportInterface
{
    /**
     * {@inheritDoc}
     */
    public function reportableSurveys(?array $companyIds): Collection
    {
        return Survey::query()
            ->whereIn('status', [SurveyStatus::Active, SurveyStatus::Closed])
            ->withCount(['responses' => fn (Builder $query) => $query->when(
                $companyIds !== null,
                fn (Builder $query) => $query->whereIn('company_id', $companyIds),
            )])
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function featuredSurvey(): ?Survey
    {
        return Survey::query()->open()->orderByDesc('start_date')->orderByDesc('id')->first()
            ?? Survey::query()
                ->whereIn('status', [SurveyStatus::Active, SurveyStatus::Closed])
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function previousSurvey(Survey $survey): ?Survey
    {
        return Survey::query()
            ->whereIn('status', [SurveyStatus::Active, SurveyStatus::Closed])
            ->whereKeyNot($survey->getKey())
            ->whereDate('start_date', '<=', $survey->start_date->toDateString())
            ->where(fn (Builder $query) => $query
                ->whereDate('start_date', '<', $survey->start_date->toDateString())
                ->orWhere('id', '<', $survey->getKey()))
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function summary(ReportScope $scope): array
    {
        $minGroupSize = $this->minGroupSize();
        $responses = $this->responseQuery($scope)->count();

        $ratingStats = $this->answersOfType($scope, QuestionType::Rating)
            ->toBase()
            ->whereNotNull('rating')
            ->selectRaw(
                'COUNT(*) as total, AVG(rating) as average, SUM(CASE WHEN rating >= ? THEN 1 ELSE 0 END) as satisfied',
                [$this->satisfiedThreshold()]
            )
            ->first();

        $criticality = $this->answersOfType($scope, QuestionType::Criticality)->whereNotNull('rating')->avg('rating');

        $participants = SurveyParticipation::query()
            ->whereBelongsTo($scope->survey)
            ->when($scope->isRestricted(), fn (Builder $query) => $query->whereIn(
                'user_id',
                User::withTrashed()->whereIn('company_id', $scope->companyIds)->select('id')
            ))
            ->count();

        $eligible = User::query()
            ->active()
            ->when($scope->isRestricted(), fn (Builder $query) => $query->whereIn('company_id', $scope->companyIds))
            ->count();

        $isReportable = $responses >= $minGroupSize;
        $ratings = (int) ($ratingStats->total ?? 0);

        return [
            'responses' => $responses,
            'participants' => $participants,
            'eligible' => $eligible,
            'participation_rate' => $eligible > 0 ? round(min(100, $participants / $eligible * 100), 1) : null,
            'average' => $isReportable && $ratings > 0 ? round((float) $ratingStats->average, 2) : null,
            'csat' => $isReportable && $ratings > 0 ? round((int) $ratingStats->satisfied / $ratings * 100, 1) : null,
            'criticality' => $isReportable && $criticality !== null ? round((float) $criticality, 2) : null,
            'ratings' => $ratings,
            'comments' => $this->answerQuery($scope)->whereNotNull('comment')->count(),
            'is_reportable' => $isReportable,
            'min_group_size' => $minGroupSize,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function departmentScores(ReportScope $scope): SupportCollection
    {
        $qualityRows = $this->groupedRatings($scope, QuestionType::Rating, 'department_id');
        $criticality = $this->averageRatings($scope, QuestionType::Criticality, 'department_id');
        $evaluations = $this->evaluationCounts($scope, 'department_id');

        $departments = Department::query()
            ->whereIn('id', $evaluations->keys()->filter())
            ->get()
            ->keyBy('id');

        return $evaluations
            ->map(function (int $evaluationCount, int $departmentId) use ($departments, $qualityRows, $criticality): array {
                /** @var Department|null $department */
                $department = $departments->get($departmentId);

                return [
                    'department_id' => $departmentId ?: null,
                    'name' => $department?->name ?? 'General',
                    'color' => $department?->color ?? 'gray',
                    'icon' => $department?->icon ?? 'message-circle',
                    'sort_order' => $department?->sort_order ?? PHP_INT_MAX,
                    'evaluations' => $evaluationCount,
                    'criticality' => $criticality->get($departmentId),
                    ...$this->scoreFromDistribution($this->distribution($qualityRows->get($departmentId, collect()))),
                ];
            })
            ->sortBy([['sort_order', 'asc'], ['name', 'asc']])
            ->values();
    }

    /**
     * {@inheritDoc}
     */
    public function sectionScores(ReportScope $scope): SupportCollection
    {
        $minGroupSize = $this->minGroupSize();
        $qualityRows = $this->groupedRatings($scope, QuestionType::Rating, 'department_section_id');
        $criticality = $this->averageRatings($scope, QuestionType::Criticality, 'department_section_id');
        $evaluations = $this->evaluationCounts($scope, 'department_section_id')->except(0);

        $sections = DepartmentSection::query()
            ->with('department')
            ->whereIn('id', $evaluations->keys())
            ->get()
            ->keyBy('id');

        return $evaluations
            ->map(function (int $evaluationCount, int $sectionId) use ($sections, $qualityRows, $criticality, $minGroupSize): array {
                /** @var DepartmentSection|null $section */
                $section = $sections->get($sectionId);
                $isHidden = $evaluationCount < $minGroupSize;

                return [
                    'section_id' => $sectionId,
                    'department_id' => $section?->department_id,
                    'name' => $section?->name ?? 'Removed section',
                    'department' => $section?->department?->name ?? 'General',
                    'color' => $section?->department?->color ?? 'gray',
                    'department_sort_order' => $section?->department?->sort_order ?? PHP_INT_MAX,
                    'sort_order' => $section?->sort_order ?? PHP_INT_MAX,
                    'evaluations' => $evaluationCount,
                    'is_hidden' => $isHidden,
                    'criticality' => $isHidden ? null : $criticality->get($sectionId),
                    ...$this->scoreFromDistribution($isHidden ? array_fill(1, 5, 0) : $this->distribution($qualityRows->get($sectionId, collect()))),
                ];
            })
            ->sortBy([['department_sort_order', 'asc'], ['department', 'asc'], ['sort_order', 'asc'], ['name', 'asc']])
            ->values();
    }

    /**
     * {@inheritDoc}
     */
    public function questionBreakdown(ReportScope $scope): SupportCollection
    {
        $ratingRows = $this->answerQuery($scope)
            ->toBase()
            ->whereNotNull('rating')
            ->selectRaw('survey_question_id, rating, COUNT(*) as total')
            ->groupBy('survey_question_id', 'rating')
            ->get()
            ->groupBy('survey_question_id');

        $commentCounts = $this->answerQuery($scope)
            ->toBase()
            ->whereNotNull('comment')
            ->selectRaw('survey_question_id, COUNT(*) as total')
            ->groupBy('survey_question_id')
            ->pluck('total', 'survey_question_id');

        return $scope->survey->orderedQuestions()
            ->map(fn (SurveyQuestion $question, int $index): array => [
                'number' => $index + 1,
                'id' => $question->id,
                'question' => $question->displayText(),
                'type' => $question->type->value,
                'type_label' => $question->type->label(),
                'scale' => $question->type->scaleLabels(),
                'department' => $question->department?->name ?? 'General',
                'section' => $question->section?->name,
                'color' => $question->department?->color ?? 'gray',
                'comments' => (int) ($commentCounts[$question->id] ?? 0),
                ...$this->scoreFromDistribution($this->distribution($ratingRows->get($question->id, collect()))),
            ]);
    }

    /**
     * {@inheritDoc}
     */
    public function companyBreakdown(ReportScope $scope): SupportCollection
    {
        $minGroupSize = $this->minGroupSize();

        $responseCounts = $this->responseQuery($scope)
            ->toBase()
            ->selectRaw('company_id, COUNT(*) as total')
            ->groupBy('company_id')
            ->get();

        $ratingStats = SurveyAnswer::query()
            ->toBase()
            ->join('survey_responses', 'survey_responses.id', '=', 'survey_answers.survey_response_id')
            ->where('survey_responses.survey_id', $scope->survey->getKey())
            ->when($scope->isRestricted(), fn ($query) => $query->whereIn('survey_responses.company_id', $scope->companyIds))
            ->whereNotNull('survey_answers.rating')
            ->selectRaw(
                'survey_responses.company_id as company_id, COUNT(*) as total, SUM(survey_answers.rating) as rating_sum, '.
                'SUM(CASE WHEN survey_answers.rating >= ? THEN 1 ELSE 0 END) as satisfied',
                [$this->satisfiedThreshold()]
            )
            ->groupBy('survey_responses.company_id')
            ->get()
            ->keyBy(fn (object $row): int => (int) ($row->company_id ?? 0));

        $companies = Company::query()
            ->whereIn('id', $responseCounts->pluck('company_id')->filter())
            ->pluck('name', 'id');

        $rows = collect();
        $other = ['responses' => 0, 'total' => 0, 'rating_sum' => 0, 'satisfied' => 0];

        foreach ($responseCounts as $responseCount) {
            $companyKey = (int) ($responseCount->company_id ?? 0);
            $stats = $ratingStats->get($companyKey);
            $responses = (int) $responseCount->total;

            if ($responses < $minGroupSize) {
                $other['responses'] += $responses;
                $other['total'] += (int) ($stats->total ?? 0);
                $other['rating_sum'] += (int) ($stats->rating_sum ?? 0);
                $other['satisfied'] += (int) ($stats->satisfied ?? 0);

                continue;
            }

            $rows->push($this->companyRow(
                $companies[$companyKey] ?? 'Unassigned company',
                $responses,
                (int) ($stats->total ?? 0),
                (int) ($stats->rating_sum ?? 0),
                (int) ($stats->satisfied ?? 0),
            ));
        }

        if ($other['responses'] > 0) {
            $otherRow = $this->companyRow(
                'Other companies (groups below '.$minGroupSize.' responses)',
                $other['responses'],
                $other['total'],
                $other['rating_sum'],
                $other['satisfied'],
            );

            if ($other['responses'] < $minGroupSize) {
                $otherRow['average'] = null;
                $otherRow['csat'] = null;
            }

            $rows->push($otherRow);
        }

        return $rows->sortByDesc('responses')->values();
    }

    /**
     * {@inheritDoc}
     */
    public function participationTrend(ReportScope $scope): array
    {
        $dailyCounts = SurveyParticipation::query()
            ->toBase()
            ->where('survey_id', $scope->survey->getKey())
            ->when($scope->isRestricted(), fn ($query) => $query->whereIn(
                'user_id',
                User::withTrashed()->whereIn('company_id', $scope->companyIds)->select('id')
            ))
            ->selectRaw('DATE(submitted_at) as day, COUNT(*) as total')
            ->groupBy(DB::raw('DATE(submitted_at)'))
            ->pluck('total', 'day');

        $trend = ['labels' => [], 'daily' => [], 'cumulative' => []];
        $end = $scope->survey->end_date->min(today());

        if ($end->lt($scope->survey->start_date)) {
            return $trend;
        }

        $runningTotal = 0;

        foreach (CarbonPeriod::create($scope->survey->start_date, $end) as $day) {
            $count = (int) ($dailyCounts[$day->toDateString()] ?? 0);
            $runningTotal += $count;

            $trend['labels'][] = $day->format('d M');
            $trend['daily'][] = $count;
            $trend['cumulative'][] = $runningTotal;
        }

        return $trend;
    }

    /**
     * {@inheritDoc}
     */
    public function comments(ReportScope $scope, array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $department = $filters['department_id'] ?? null;
        $search = $filters['search'] ?? null;

        return $this->answerQuery($scope)
            ->whereNotNull('comment')
            ->with(['question:id,question', 'department:id,name,color', 'section:id,name'])
            ->when($department === 'general', fn (Builder $query) => $query->whereNull('department_id'))
            ->when(is_numeric($department), fn (Builder $query) => $query->where('department_id', (int) $department))
            ->when(filled($search), fn (Builder $query) => $query->where('comment', 'like', Apps::likePattern((string) $search)))
            ->orderBy('id')
            ->paginate($perPage);
    }

    /**
     * Responses of the survey inside the scope.
     *
     * @return Builder<SurveyResponse>
     */
    protected function responseQuery(ReportScope $scope): Builder
    {
        return SurveyResponse::query()
            ->where('survey_id', $scope->survey->getKey())
            ->when($scope->isRestricted(), fn (Builder $query) => $query->whereIn('company_id', $scope->companyIds));
    }

    /**
     * Answers belonging to the responses inside the scope.
     *
     * @return Builder<SurveyAnswer>
     */
    protected function answerQuery(ReportScope $scope): Builder
    {
        return SurveyAnswer::query()->whereIn('survey_response_id', $this->responseQuery($scope)->select('id'));
    }

    /**
     * Answers of the scope to questions of one type.
     *
     * @return Builder<SurveyAnswer>
     */
    protected function answersOfType(ReportScope $scope, QuestionType $type): Builder
    {
        return $this->answerQuery($scope)->whereIn(
            'survey_question_id',
            SurveyQuestion::query()->where('survey_id', $scope->survey->getKey())->where('type', $type->value)->select('id')
        );
    }

    /**
     * Rating counts per value, grouped by a column (department or section).
     *
     * @return SupportCollection<int, SupportCollection<int, object>>
     */
    protected function groupedRatings(ReportScope $scope, QuestionType $type, string $column): SupportCollection
    {
        return $this->answersOfType($scope, $type)
            ->toBase()
            ->whereNotNull('rating')
            ->selectRaw("{$column} as group_key, rating, COUNT(*) as total")
            ->groupBy($column, 'rating')
            ->get()
            ->groupBy(fn (object $row): int => (int) ($row->group_key ?? 0));
    }

    /**
     * Average rating grouped by a column (department or section).
     *
     * @return SupportCollection<int, float>
     */
    protected function averageRatings(ReportScope $scope, QuestionType $type, string $column): SupportCollection
    {
        return $this->answersOfType($scope, $type)
            ->toBase()
            ->whereNotNull('rating')
            ->selectRaw("{$column} as group_key, AVG(rating) as average")
            ->groupBy($column)
            ->get()
            ->mapWithKeys(fn (object $row): array => [(int) ($row->group_key ?? 0) => round((float) $row->average, 2)]);
    }

    /**
     * Number of responses that evaluated each department or section.
     *
     * @return SupportCollection<int, int>
     */
    protected function evaluationCounts(ReportScope $scope, string $column): SupportCollection
    {
        return $this->answerQuery($scope)
            ->toBase()
            ->selectRaw("{$column} as group_key, COUNT(DISTINCT survey_response_id) as total")
            ->groupBy($column)
            ->get()
            ->mapWithKeys(fn (object $row): array => [(int) ($row->group_key ?? 0) => (int) $row->total]);
    }

    /**
     * Count of each rating value (1-5) from grouped rows.
     *
     * @param  SupportCollection<int, object>  $rows
     * @return array<int, int>
     */
    protected function distribution(SupportCollection $rows): array
    {
        $distribution = array_fill(1, 5, 0);

        foreach ($rows as $row) {
            $rating = (int) $row->rating;

            if (isset($distribution[$rating])) {
                $distribution[$rating] = (int) $row->total;
            }
        }

        return $distribution;
    }

    /**
     * Average, CSAT and count derived from a rating distribution.
     *
     * @param  array<int, int>  $distribution
     * @return array{ratings: int, average: float|null, csat: float|null, distribution: array<int, int>}
     */
    protected function scoreFromDistribution(array $distribution): array
    {
        $total = array_sum($distribution);
        $weighted = 0;
        $satisfied = 0;

        foreach ($distribution as $rating => $count) {
            $weighted += $rating * $count;

            if ($rating >= $this->satisfiedThreshold()) {
                $satisfied += $count;
            }
        }

        return [
            'ratings' => $total,
            'average' => $total > 0 ? round($weighted / $total, 2) : null,
            'csat' => $total > 0 ? round($satisfied / $total * 100, 1) : null,
            'distribution' => $distribution,
        ];
    }

    /**
     * A company breakdown row.
     *
     * @return array{company: string, responses: int, average: float|null, csat: float|null}
     */
    protected function companyRow(string $company, int $responses, int $ratings, int $ratingSum, int $satisfied): array
    {
        return [
            'company' => $company,
            'responses' => $responses,
            'average' => $ratings > 0 ? round($ratingSum / $ratings, 2) : null,
            'csat' => $ratings > 0 ? round($satisfied / $ratings * 100, 1) : null,
        ];
    }

    /**
     * Minimum number of responses before results of a group are shown.
     */
    protected function minGroupSize(): int
    {
        return max(1, (int) config('survey.min_group_size', 5));
    }

    /**
     * Lowest rating counted as "satisfied" for the CSAT percentage.
     */
    protected function satisfiedThreshold(): int
    {
        return (int) config('survey.satisfied_threshold', 4);
    }
}
