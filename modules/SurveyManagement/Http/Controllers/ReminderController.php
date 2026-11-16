<?php

namespace Modules\SurveyManagement\Http\Controllers;

use App\Helpers\Apps;
use App\Http\Controllers\Controller;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyManualReminder;
use App\Models\Survey\SurveyReminder;
use App\Models\UserManagement\User;
use App\Services\Audit\AuditLogger;
use App\Services\Survey\SurveyReminders;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Modules\SurveyManagement\Http\Requests\SendReminderRequest;

/**
 * Reminders sent by hand about an open survey: to everyone who has not
 * completed it, or to chosen people (handy for a test send to oneself).
 * Each person gets the same in-app notification and e-mail as the daily
 * scheduled reminder, in their own language.
 */
class ReminderController extends Controller
{
    /**
     * Number of sends shown in the history.
     */
    protected const HISTORY_SIZE = 30;

    /**
     * Create a new controller instance.
     */
    public function __construct(protected SurveyReminders $reminders) {}

    /**
     * Reminder page: the send form and the history of sends.
     */
    public function index(Request $request): View
    {
        $surveys = Survey::query()->open()->orderBy('end_date')->get();

        return view('SurveyManagement::reminder.index', [
            'surveys' => $surveys,
            'pendingCounts' => $surveys->mapWithKeys(fn (Survey $survey): array => [
                $survey->id => $this->reminders->pendingFor($survey)->count(),
            ]),
            'history' => $this->history(),
            'maxChosen' => SendReminderRequest::MAX_CHOSEN,
            'can' => $request->user()->menuAbilities('survey-management.reminder'),
        ]);
    }

    /**
     * People who may be chosen (JSON for the search box): active users whose
     * role fills in surveys; those who completed the survey are disabled.
     */
    public function recipients(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'survey_id' => ['nullable', 'integer'],
        ]);

        $users = $this->reminders->surveyTakers()
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where(
                fn (Builder $query) => $query
                    ->where('name', 'like', Apps::likePattern($search))
                    ->orWhere('email', 'like', Apps::likePattern($search))
                    ->orWhere('employee_id', 'like', Apps::likePattern($search))
            ))
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'email']);

        $survey = filled($filters['survey_id'] ?? null) ? Survey::query()->find($filters['survey_id']) : null;
        $completedIds = $survey === null ? [] : $survey->participations()
            ->whereIn('user_id', $users->modelKeys())
            ->pluck('user_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        return response()->json([
            'data' => $users->map(function (User $user) use ($completedIds): array {
                $hasCompleted = in_array($user->id, $completedIds, true);

                return [
                    'id' => $user->id,
                    'text' => $user->name.' — '.$user->email.($hasCompleted ? ' ('.__('completed').')' : ''),
                    'disabled' => $hasCompleted,
                ];
            })->values(),
        ]);
    }

    /**
     * Send the reminder.
     */
    public function store(SendReminderRequest $request, AuditLogger $audit): JsonResponse
    {
        $survey = $request->survey();
        $send = $this->reminders->sendManually($survey, $request->user(), $request->recipientIds());
        $audit->log(AuditLogger::REMINDER_SENT, $send, new: [
            'Survey' => $survey->title,
            'Recipients' => $send->audience === SurveyManualReminder::AUDIENCE_ALL ? 'Everyone who has not completed it' : 'Chosen people',
            'Reminded' => $send->recipients,
            'Left out' => $send->skipped,
        ], label: $survey->title);

        $message = __('Reminder sent to :count people.', ['count' => $send->recipients]);

        if ($send->skipped > 0) {
            $message .= ' '.__(':count chosen people were left out: they completed the survey already, are inactive or do not fill in surveys.', ['count' => $send->skipped]);
        }

        return response()->json([
            'message' => $message,
            'data' => ['recipients' => $send->recipients, 'skipped' => $send->skipped],
        ]);
    }

    /**
     * The latest sends, by hand and scheduled, newest first.
     *
     * @return Collection<int, array{sent_at: Carbon, survey: string|null, audience: string, recipients: int, skipped: int, sent_by: string}>
     */
    protected function history(): Collection
    {
        $manual = SurveyManualReminder::query()
            ->with(['survey:id,title', 'sender:id,name'])
            ->latest('sent_at')
            ->limit(self::HISTORY_SIZE)
            ->get()
            ->map(fn (SurveyManualReminder $send): array => [
                'sent_at' => $send->sent_at,
                'survey' => $send->survey?->title,
                'audience' => $send->audience === SurveyManualReminder::AUDIENCE_ALL
                    ? __('Everyone who has not completed it')
                    : __('Chosen people'),
                'recipients' => $send->recipients,
                'skipped' => $send->skipped,
                'sent_by' => $send->sender?->name ?? '-',
            ]);

        $scheduled = SurveyReminder::query()
            ->with('survey:id,title')
            ->latest('sent_at')
            ->limit(self::HISTORY_SIZE)
            ->get()
            ->map(fn (SurveyReminder $send): array => [
                'sent_at' => $send->sent_at,
                'survey' => $send->survey?->title,
                'audience' => __('Scheduled (:days days before the end)', ['days' => $send->days_before]),
                'recipients' => $send->recipients,
                'skipped' => 0,
                'sent_by' => __('System'),
            ]);

        return $manual->concat($scheduled)
            ->sortByDesc(fn (array $send): int => $send['sent_at']->getTimestamp())
            ->take(self::HISTORY_SIZE)
            ->values();
    }
}
