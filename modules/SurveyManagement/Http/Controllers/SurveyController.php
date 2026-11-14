<?php

namespace Modules\SurveyManagement\Http\Controllers;

use App\Enums\SurveyStatus;
use App\Helpers\Apps;
use App\Http\Controllers\Controller;
use App\Models\Survey\Survey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Modules\SurveyManagement\Http\Requests\SurveyRequest;
use Modules\SurveyManagement\Repositories\SurveyInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Survey periods (e.g. "Operational Audit 2026").
 */
class SurveyController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(protected SurveyInterface $surveys) {}

    /**
     * Survey period page.
     */
    public function index(Request $request): View
    {
        return view('SurveyManagement::survey.index', [
            'statuses' => SurveyStatus::options(),
            'can' => $request->user()->menuAbilities('survey-management.survey'),
            'canManageQuestions' => $request->user()->canAccessMenu('survey-management.question'),
            'canViewReports' => $request->user()->canAccessMenu('survey-report'),
        ]);
    }

    /**
     * Survey periods for the table (JSON).
     */
    public function list(Request $request): JsonResponse
    {
        $params = Apps::tableParams($request, ['title', 'start_date', 'end_date', 'status', 'estimated_minutes'], 'start_date', 'desc');

        $surveys = $this->surveys->paginate($params, [
            'status' => in_array($request->query('status'), array_keys(SurveyStatus::options()), true) ? $request->query('status') : null,
        ]);

        return Apps::tableResponse($surveys, fn (Survey $survey): array => [
            'id' => $survey->id,
            'title' => $survey->title,
            'period' => $survey->periodLabel(),
            'status' => $survey->status->value,
            'status_label' => $survey->status->label(),
            'status_color' => $survey->status->color(),
            'is_open' => $survey->isOpen(),
            'estimated_minutes' => $survey->estimated_minutes,
            'questions_count' => $survey->questions_count,
            'responses_count' => $survey->responses_count,
        ]);
    }

    /**
     * A survey period (JSON for the edit modal).
     */
    public function show(Survey $survey): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $survey->id,
                'title' => $survey->title,
                'description' => $survey->description,
                'start_date' => $survey->start_date->toDateString(),
                'end_date' => $survey->end_date->toDateString(),
                'status' => $survey->status->value,
                'estimated_minutes' => $survey->estimated_minutes,
            ],
        ]);
    }

    /**
     * Create a survey period.
     */
    public function store(SurveyRequest $request): JsonResponse
    {
        $survey = $this->surveys->create($request->validated(), $request->user());

        return response()->json([
            'message' => 'Survey created successfully.',
            'data' => ['id' => $survey->id],
        ], Response::HTTP_CREATED);
    }

    /**
     * Update a survey period.
     */
    public function update(SurveyRequest $request, Survey $survey): JsonResponse
    {
        $this->surveys->update($survey, $request->validated());

        return response()->json(['message' => 'Survey updated successfully.']);
    }

    /**
     * Publish, close or move back to draft.
     */
    public function updateStatus(Request $request, Survey $survey): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(SurveyStatus::class)],
        ]);

        $status = SurveyStatus::from($validated['status']);

        if ($status === SurveyStatus::Active && $survey->questions()->doesntExist()) {
            throw ValidationException::withMessages([
                'status' => 'Add at least one question before activating the survey.',
            ]);
        }

        $this->surveys->changeStatus($survey, $status);

        return response()->json(['message' => 'Survey status changed to '.$status->label().'.']);
    }

    /**
     * Copy a survey and its questions into a new draft.
     */
    public function duplicate(Request $request, Survey $survey): JsonResponse
    {
        $copy = $this->surveys->duplicate($survey, $request->user());

        return response()->json([
            'message' => 'Survey duplicated as draft "'.$copy->title.'".',
            'data' => ['id' => $copy->id],
        ], Response::HTTP_CREATED);
    }

    /**
     * Delete a survey without responses.
     */
    public function destroy(Survey $survey): JsonResponse
    {
        if ($survey->hasResponses()) {
            return response()->json([
                'message' => 'This survey already has responses and cannot be deleted. Close it instead.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->surveys->delete($survey);

        return response()->json(['message' => 'Survey deleted successfully.']);
    }
}
