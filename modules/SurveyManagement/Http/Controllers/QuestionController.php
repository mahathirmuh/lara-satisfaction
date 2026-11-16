<?php

namespace Modules\SurveyManagement\Http\Controllers;

use App\Enums\QuestionType;
use App\Helpers\Apps;
use App\Http\Controllers\Controller;
use App\Models\Organization\Department;
use App\Models\Organization\DepartmentSection;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyQuestion;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\SurveyManagement\Http\Requests\QuestionRequest;
use Modules\SurveyManagement\Repositories\QuestionInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Questions of the survey periods, grouped by assessed department.
 */
class QuestionController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(protected QuestionInterface $questions) {}

    /**
     * Question page.
     */
    public function index(Request $request): View
    {
        $surveys = Survey::query()
            ->withCount('responses')
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get(['id', 'title', 'status', 'start_date', 'end_date']);

        return view('SurveyManagement::question.index', [
            'surveys' => $surveys,
            'departments' => Department::query()->ordered()->get(['id', 'name', 'is_active']),
            'sections' => DepartmentSection::query()->ordered()->get(['id', 'department_id', 'name', 'is_active']),
            'types' => QuestionType::options(),
            'selectedSurveyId' => $request->integer('survey_id') ?: $surveys->first()?->id,
            'can' => $request->user()->menuAbilities('survey-management.question'),
        ]);
    }

    /**
     * Questions for the table (JSON).
     */
    public function list(Request $request): JsonResponse
    {
        $params = Apps::tableParams($request, ['sort_order', 'question', 'type', 'is_required'], 'sort_order');

        $questions = $this->questions->paginate($params, [
            'survey_id' => $request->query('survey_id'),
            'department_id' => $request->query('department_id'),
            'department_section_id' => $request->query('department_section_id'),
            'type' => in_array($request->query('type'), array_keys(QuestionType::options()), true) ? $request->query('type') : null,
        ]);

        return Apps::tableResponse($questions, fn (SurveyQuestion $question): array => [
            'id' => $question->id,
            'survey' => $question->survey?->title,
            'department' => $question->department?->name ?? __('General'),
            'department_color' => $question->department?->color ?? 'gray',
            'section' => $question->section?->name,
            'type' => $question->type->value,
            'type_label' => $question->type->label(),
            'question' => $question->question,
            'help_text' => $question->help_text,
            'is_required' => $question->is_required,
            'sort_order' => $question->sort_order,
            'is_locked' => ($question->survey?->responses_count ?? 0) > 0,
        ]);
    }

    /**
     * A question (JSON for the edit modal).
     */
    public function show(SurveyQuestion $question): JsonResponse
    {
        return response()->json([
            'data' => [
                ...$question->only(['id', 'survey_id', 'department_id', 'department_section_id', 'question', 'help_text', 'is_required', 'sort_order']),
                'type' => $question->type->value,
            ],
        ]);
    }

    /**
     * Create a question.
     */
    public function store(QuestionRequest $request, AuditLogger $audit): JsonResponse
    {
        $question = $this->questions->create($request->validated());
        $audit->log(AuditLogger::QUESTION_CREATED, $question, new: $question->refresh()->auditValues(), label: $question->auditLabel());

        return response()->json([
            'message' => __('Question created successfully.'),
            'data' => ['id' => $question->id],
        ], Response::HTTP_CREATED);
    }

    /**
     * Update a question.
     */
    public function update(QuestionRequest $request, SurveyQuestion $question, AuditLogger $audit): JsonResponse
    {
        $before = $question->auditValues();
        $this->questions->update($question, $request->validated());
        $audit->logChanges(AuditLogger::QUESTION_UPDATED, $question, $before, $question->refresh()->auditValues(), $question->auditLabel());

        return response()->json(['message' => __('Question updated successfully.')]);
    }

    /**
     * Delete a question of a survey without responses.
     */
    public function destroy(SurveyQuestion $question, AuditLogger $audit): JsonResponse
    {
        if ($question->survey()->whereHas('responses')->exists()) {
            return response()->json([
                'message' => __('This survey already has responses, so its questions can no longer be changed.'),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $before = $question->auditValues();
        $this->questions->delete($question);
        $audit->log(AuditLogger::QUESTION_DELETED, $question, old: $before, label: $question->auditLabel());

        return response()->json(['message' => __('Question deleted successfully.')]);
    }
}
