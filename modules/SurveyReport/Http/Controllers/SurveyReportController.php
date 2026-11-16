<?php

namespace Modules\SurveyReport\Http\Controllers;

use App\Enums\QuestionType;
use App\Enums\SurveyStatus;
use App\Helpers\Alert;
use App\Helpers\Apps;
use App\Http\Controllers\Controller;
use App\Models\Organization\Company;
use App\Models\Organization\Department;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyAnswer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\SurveyReport\Repositories\SurveyReportInterface;
use Modules\SurveyReport\Services\SurveyResultExporter;
use Modules\SurveyReport\Support\ReportScope;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Anonymous survey results, limited to the companies of the user's role scope
 * and, for managers, to the departments their job title manages.
 */
class SurveyReportController extends Controller
{
    /**
     * Shown to a manager whose job title manages none of the surveyed departments.
     */
    public const NO_DEPARTMENT_MESSAGE = 'Your job title is not set as the manager of a surveyed department. Ask the IT Admin to add it in Master Data > Departments.';

    /**
     * Create a new controller instance.
     */
    public function __construct(protected SurveyReportInterface $reports) {}

    /**
     * Surveys with a report.
     */
    public function index(Request $request): View
    {
        return view('SurveyReport::index', [
            'surveys' => $this->reports->reportableSurveys($request->user()->scopedCompanyIds()),
            'noDepartmentMessage' => $request->user()->reportDepartmentIds() === [] ? self::NO_DEPARTMENT_MESSAGE : null,
        ]);
    }

    /**
     * Report of one survey.
     */
    public function show(Request $request, Survey $survey): View
    {
        $this->ensureReportable($survey);

        $scope = $this->scopeFor($request, $survey);
        $summary = $this->reports->summary($scope);
        $isReportable = $summary['is_reportable'];

        return view('SurveyReport::show', [
            'survey' => $survey,
            'summary' => $summary,
            'departmentScores' => $isReportable ? $this->reports->departmentScores($scope) : collect(),
            'sectionScores' => $isReportable ? $this->reports->sectionScores($scope) : collect(),
            'questionBreakdown' => $isReportable ? $this->reports->questionBreakdown($scope) : collect(),
            'companyBreakdown' => $isReportable && $this->isMultiCompany($scope) ? $this->reports->companyBreakdown($scope) : collect(),
            'trend' => $this->reports->participationTrend($scope),
            'companies' => $this->companyOptions($request),
            'selectedCompanyId' => $request->integer('company_id') ?: null,
            'departments' => $survey->departments()->filter(fn (Department $department): bool => $scope->coversDepartment($department->id))->values(),
            'ratingScale' => QuestionType::Rating->scaleLabels(),
        ]);
    }

    /**
     * Anonymous comments (JSON for the comments table).
     */
    public function comments(Request $request, Survey $survey): JsonResponse
    {
        $this->ensureReportable($survey);

        $filters = $request->validate([
            'department_id' => ['nullable', 'regex:/^(general|\d+)$/'],
            'search' => ['nullable', 'string', 'max:100'],
            'company_id' => ['nullable', 'integer'],
        ]);

        $scope = $this->scopeFor($request, $survey);

        abort_unless($this->reports->summary($scope)['is_reportable'], Response::HTTP_FORBIDDEN, 'Not enough responses to protect respondent anonymity.');

        $params = Apps::tableParams($request, [], 'id');
        $comments = $this->reports->comments($scope, $filters, $params['per_page']);

        return Apps::tableResponse($comments, fn (SurveyAnswer $answer): array => [
            'question' => $answer->question?->question,
            'department' => $answer->department?->name ?? 'General',
            'section' => $answer->section?->name,
            'color' => $answer->department?->color ?? 'gray',
            'comment' => $answer->comment,
        ]);
    }

    /**
     * Download the anonymous responses as CSV.
     */
    public function export(Request $request, Survey $survey, SurveyResultExporter $exporter): StreamedResponse|RedirectResponse
    {
        $this->ensureReportable($survey);

        $scope = $this->scopeFor($request, $survey);

        if (! $this->reports->summary($scope)['is_reportable']) {
            Alert::warning('Export unavailable', 'At least '.config('survey.min_group_size').' responses are required to protect respondent anonymity.');

            return redirect()->route('survey-report.show', array_filter([
                'survey' => $survey,
                'company_id' => $request->integer('company_id') ?: null,
            ]));
        }

        $filename = Str::slug($survey->title).'-responses-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($exporter, $scope): void {
            $stream = fopen('php://output', 'wb');
            $exporter->write($scope, $stream);
            fclose($stream);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Draft surveys have no results.
     */
    protected function ensureReportable(Survey $survey): void
    {
        abort_if($survey->status === SurveyStatus::Draft, Response::HTTP_NOT_FOUND);
    }

    /**
     * Report scope from the user's role scope (companies and departments) and
     * the optional company filter.
     */
    protected function scopeFor(Request $request, Survey $survey): ReportScope
    {
        $allowedCompanyIds = $request->user()->scopedCompanyIds();
        $departmentIds = $request->user()->reportDepartmentIds();
        $companyId = $request->integer('company_id') ?: null;

        abort_if($departmentIds === [], Response::HTTP_FORBIDDEN, self::NO_DEPARTMENT_MESSAGE);

        if ($companyId === null) {
            return new ReportScope($survey, $allowedCompanyIds, $departmentIds);
        }

        abort_if(
            $allowedCompanyIds !== null && ! in_array($companyId, $allowedCompanyIds, true),
            Response::HTTP_FORBIDDEN,
            'You may not view results of this company.'
        );

        return new ReportScope($survey, [$companyId], $departmentIds);
    }

    /**
     * Whether a company breakdown is meaningful for the scope.
     */
    protected function isMultiCompany(ReportScope $scope): bool
    {
        return $scope->companyIds === null || count($scope->companyIds) > 1;
    }

    /**
     * Companies the user may filter on.
     *
     * @return Collection<int, string>
     */
    protected function companyOptions(Request $request): Collection
    {
        $allowedCompanyIds = $request->user()->scopedCompanyIds();

        return Company::query()
            ->when($allowedCompanyIds !== null, fn ($query) => $query->whereIn('id', $allowedCompanyIds))
            ->orderBy('name')
            ->pluck('name', 'id');
    }
}
