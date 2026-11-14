<?php

namespace Modules\SurveyForm\Http\Controllers;

use App\Helpers\Alert;
use App\Http\Controllers\Controller;
use App\Models\Survey\Survey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\SurveyForm\Exceptions\SurveySubmissionException;
use Modules\SurveyForm\Http\Requests\SaveDraftRequest;
use Modules\SurveyForm\Http\Requests\SubmitSurveyRequest;
use Modules\SurveyForm\Repositories\SurveyFormInterface;
use Modules\SurveyForm\Services\SurveySubmissionService;
use Symfony\Component\HttpFoundation\Response;

/**
 * Employee side of the survey: list, fill in (wizard) and submit anonymously.
 */
class SurveyFormController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(protected SurveyFormInterface $surveyForm) {}

    /**
     * Surveys the user can fill in today and their completion history.
     */
    public function index(Request $request): View
    {
        return view('SurveyForm::index', [
            'surveys' => $this->surveyForm->availableFor($request->user()),
            'history' => $this->surveyForm->participationHistory($request->user()),
        ]);
    }

    /**
     * The survey wizard: personal data, then per assessed department every
     * section with its overview and questions.
     */
    public function show(Request $request, Survey $survey): View|RedirectResponse
    {
        if ($redirect = $this->redirectIfUnavailable($request, $survey)) {
            return $redirect;
        }

        $draft = $this->surveyForm->draftFor($survey, $request->user());

        return view('SurveyForm::show', [
            'survey' => $survey,
            'respondent' => $request->user()->loadMissing('company'),
            'steps' => $this->surveyForm->stepsFor($survey),
            'draft' => $draft === null ? null : [
                'values' => $draft->formValues(),
                'step' => (int) ($draft->payload['step'] ?? 0),
                'saved_at' => $draft->updated_at->toIso8601String(),
                'saved_label' => $draft->updated_at->format('d M Y H:i'),
            ],
            'commentMaxLength' => (int) config('survey.comment_max_length', 500),
            'feedbackGuidelines' => config('survey.feedback_guidelines', []),
        ]);
    }

    /**
     * Save the unfinished answers to continue later (JSON).
     */
    public function saveDraft(SaveDraftRequest $request, Survey $survey): JsonResponse
    {
        if (! $survey->isOpen() || $this->surveyForm->hasParticipated($survey, $request->user())) {
            return response()->json([
                'message' => 'This survey is closed or already submitted, so the draft cannot be saved.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $draft = $this->surveyForm->saveDraft($survey, $request->user(), [
            'answers' => $request->validated('answers') ?? [],
            'evaluations' => $request->validated('evaluations') ?? [],
            'step' => $request->validated('step'),
        ]);

        return response()->json([
            'message' => 'Draft saved. You can continue later from My Survey, also on another device.',
            'saved_at' => $draft->updated_at->toIso8601String(),
            'saved_label' => $draft->updated_at->format('d M Y H:i'),
        ]);
    }

    /**
     * Submit the answers anonymously.
     */
    public function store(SubmitSurveyRequest $request, Survey $survey, SurveySubmissionService $submission): RedirectResponse
    {
        if ($redirect = $this->redirectIfUnavailable($request, $survey)) {
            return $redirect;
        }

        try {
            $submission->submit(
                $survey,
                $request->user(),
                $request->validated('answers') ?? [],
                $request->validated('evaluations') ?? [],
            );
        } catch (SurveySubmissionException $exception) {
            Alert::warning('Submission not saved', $exception->getMessage());

            return redirect()->route('survey-form.index');
        }

        return redirect()->route('survey-form.thanks', $survey);
    }

    /**
     * Confirmation page after a successful submission.
     */
    public function thanks(Request $request, Survey $survey): View|RedirectResponse
    {
        if (! $this->surveyForm->hasParticipated($survey, $request->user())) {
            return redirect()->route('survey-form.index');
        }

        return view('SurveyForm::thanks', [
            'survey' => $survey,
        ]);
    }

    /**
     * Redirect back to the survey list when the survey cannot be filled in.
     */
    protected function redirectIfUnavailable(Request $request, Survey $survey): ?RedirectResponse
    {
        if (! $survey->isOpen() || $survey->questions()->doesntExist()) {
            Alert::warning('Survey unavailable', 'This survey is not open for responses.');

            return redirect()->route('survey-form.index');
        }

        if ($this->surveyForm->hasParticipated($survey, $request->user())) {
            Alert::info('Already submitted', 'You have already completed this survey. Thank you!');

            return redirect()->route('survey-form.index');
        }

        return null;
    }
}
