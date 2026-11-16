<?php

namespace Modules\SurveyProgress\Http\Controllers;

use App\Enums\ParticipationStatus;
use App\Helpers\Apps;
use App\Http\Controllers\Controller;
use App\Models\Organization\Company;
use App\Models\Survey\Survey;
use App\Models\UserManagement\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\SurveyProgress\Repositories\SurveyProgressInterface;

/**
 * Where every employee stands with a survey: Not Started, In Progress (opened
 * the form, possibly with a saved draft) or Completed. Only the filling
 * status: answers stay anonymous and are never linked to a person.
 */
class SurveyProgressController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(protected SurveyProgressInterface $surveyProgress) {}

    /**
     * Progress page of the chosen survey (?survey=id), the latest by default.
     */
    public function index(Request $request): View
    {
        $surveys = $this->surveyProgress->surveys();
        $survey = $surveys->firstWhere('id', $request->integer('survey')) ?? $surveys->first();

        return view('SurveyProgress::index', [
            'surveys' => $surveys,
            'survey' => $survey,
            'summary' => $survey === null ? null : $this->surveyProgress->summary($survey),
            'statuses' => ParticipationStatus::options(),
            'companies' => Company::query()->orderBy('name')->get(['id', 'code', 'name']),
            'canSendReminders' => $request->user()->canAccessMenu('survey-management.reminder'),
        ]);
    }

    /**
     * People and their status for the table (JSON).
     */
    public function list(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'survey_id' => ['required', 'integer', Rule::exists('surveys', 'id')],
            'status' => ['nullable', Rule::enum(ParticipationStatus::class)],
            'company' => ['nullable', 'array'],
            'company.*' => ['integer'],
        ]);

        $survey = Survey::query()->findOrFail($filters['survey_id']);
        $params = Apps::tableParams($request, ['name', 'employee_id', 'job_title'], 'name');

        return Apps::tableResponse(
            $this->surveyProgress->paginate($survey, $params, $filters),
            function (User $user): array {
                $status = ParticipationStatus::resolve($user->completed_at !== null, $user->draft_saved_at !== null, $user->started_at !== null);
                $lastActivity = $user->completed_at ?? $user->draft_saved_at ?? $user->started_at;

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'employee_id' => $user->employee_id,
                    'job_title' => $user->job_title,
                    'company' => $user->company?->name,
                    'status' => $status->value,
                    'status_label' => $status->label(),
                    'status_color' => $status->color(),
                    'saved_draft' => $status === ParticipationStatus::InProgress && $user->draft_saved_at !== null,
                    'last_activity' => $lastActivity === null ? null : Carbon::parse($lastActivity)->translatedFormat(__('d M Y')),
                ];
            },
        );
    }
}
