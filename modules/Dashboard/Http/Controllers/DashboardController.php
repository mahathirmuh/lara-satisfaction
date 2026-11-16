<?php

namespace Modules\Dashboard\Http\Controllers;

use App\Enums\PermissionAction;
use App\Enums\QuestionType;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\SurveyForm\Repositories\SurveyFormInterface;
use Modules\SurveyReport\Repositories\SurveyReportInterface;
use Modules\SurveyReport\Support\ReportScope;

/**
 * Home page: open surveys for every employee, plus the analytics of the
 * featured survey for users allowed to read survey reports.
 */
class DashboardController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected SurveyFormInterface $surveyForm,
        protected SurveyReportInterface $reports,
    ) {}

    /**
     * Show the dashboard.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $departmentIds = $user->reportDepartmentIds();
        $canViewReports = $user->canAccessMenu('survey-report', PermissionAction::Read) && $departmentIds !== [];

        $data = [
            'openSurveys' => $this->surveyForm->availableFor($user),
            'canTakeSurveys' => $user->canAccessMenu('survey-form', PermissionAction::Read),
            'canViewReports' => $canViewReports,
            'featuredSurvey' => null,
        ];

        if (! $canViewReports || ($featuredSurvey = $this->reports->featuredSurvey()) === null) {
            return view('Dashboard::index', $data);
        }

        $companyIds = $user->scopedCompanyIds();
        $scope = new ReportScope($featuredSurvey, $companyIds, $departmentIds);
        $summary = $this->reports->summary($scope);
        $previousSurvey = $this->reports->previousSurvey($featuredSurvey);

        return view('Dashboard::index', [
            ...$data,
            'featuredSurvey' => $featuredSurvey,
            'summary' => $summary,
            'previousSurvey' => $previousSurvey,
            'previousSummary' => $previousSurvey ? $this->reports->summary(new ReportScope($previousSurvey, $companyIds, $departmentIds)) : null,
            'departmentScores' => $summary['is_reportable'] ? $this->reports->departmentScores($scope) : collect(),
            'trend' => $this->reports->participationTrend($scope),
            'latestComments' => $summary['is_reportable'] ? $this->reports->comments($scope, [], 5)->getCollection() : collect(),
            'ratingScale' => QuestionType::Rating->scaleLabels(),
        ]);
    }
}
