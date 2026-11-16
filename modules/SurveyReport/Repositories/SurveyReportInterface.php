<?php

namespace Modules\SurveyReport\Repositories;

use App\Models\Survey\Survey;
use App\Models\Survey\SurveyAnswer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;
use Modules\SurveyReport\Support\ReportScope;

/**
 * Aggregated, anonymity-preserving survey analytics.
 */
interface SurveyReportInterface
{
    /**
     * Surveys that have a report (active or closed), newest first, with their
     * number of responses.
     *
     * @return Collection<int, Survey>
     */
    public function reportableSurveys(): Collection;

    /**
     * Survey highlighted on the dashboard: the latest open survey, otherwise
     * the latest reportable one.
     */
    public function featuredSurvey(): ?Survey;

    /**
     * The reportable survey that ran before the given one.
     */
    public function previousSurvey(Survey $survey): ?Survey;

    /**
     * Headline numbers. Average, CSAT and ratings cover the service quality
     * questions only; criticality is the average of the criticality
     * questions. Scores are null when the scope has fewer responses than the
     * anonymity threshold.
     *
     * @return array{responses: int, participants: int, eligible: int, participation_rate: float|null, average: float|null, csat: float|null, criticality: float|null, ratings: int, comments: int, is_reportable: bool, min_group_size: int}
     */
    public function summary(ReportScope $scope): array;

    /**
     * Scores per assessed department (general questions last): number of
     * responses that evaluated it, average criticality and the service
     * quality score.
     *
     * @return SupportCollection<int, array{department_id: int|null, name: string, color: string, icon: string, sort_order: int, evaluations: int, criticality: float|null, ratings: int, average: float|null, csat: float|null, distribution: array<int, int>}>
     */
    public function departmentScores(ReportScope $scope): SupportCollection;

    /**
     * Scores per section that employees chose to evaluate, in department then
     * section display order. Sections evaluated by fewer responses than the
     * anonymity threshold are listed with hidden scores.
     *
     * @return SupportCollection<int, array{section_id: int, department_id: int|null, name: string, department: string, color: string, department_sort_order: int, sort_order: int, evaluations: int, is_hidden: bool, criticality: float|null, ratings: int, average: float|null, csat: float|null, distribution: array<int, int>}>
     */
    public function sectionScores(ReportScope $scope): SupportCollection;

    /**
     * Score distribution per question. The question text is translated (the
     * English text of a standard question is its translation key; questions
     * written by admins stay as typed) and "{section}" is shown as "[section]".
     *
     * @return SupportCollection<int, array<string, mixed>>
     */
    public function questionBreakdown(ReportScope $scope): SupportCollection;

    /**
     * Scores per respondent company; companies below the anonymity threshold
     * are merged into a single "other" row.
     *
     * @return SupportCollection<int, array{company: string, responses: int, average: float|null, csat: float|null}>
     */
    public function companyBreakdown(ReportScope $scope): SupportCollection;

    /**
     * Daily and cumulative completions over the survey period.
     *
     * @return array{labels: array<int, string>, daily: array<int, int>, cumulative: array<int, int>}
     */
    public function participationTrend(ReportScope $scope): array;

    /**
     * Anonymous comments, optionally filtered by department ("general" for
     * general questions) and search text.
     *
     * @param  array{department_id?: int|string|null, search?: string|null}  $filters
     * @return LengthAwarePaginator<int, SurveyAnswer>
     */
    public function comments(ReportScope $scope, array $filters = [], int $perPage = 10): LengthAwarePaginator;
}
