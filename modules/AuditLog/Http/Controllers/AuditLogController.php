<?php

namespace Modules\AuditLog\Http\Controllers;

use App\Helpers\Apps;
use App\Http\Controllers\Controller;
use App\Models\Log\AuditLog;
use App\Services\Audit\AuditLogger;
use App\Services\Audit\UserAgent;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\AuditLog\Repositories\AuditLogInterface;
use Modules\AuditLog\Services\AuditVocabulary;

/**
 * The audit trail, read only (menu "Audit Log", as in MTI Radio Management). There is no action that changes or deletes
 * an entry, by design; the values were redacted when they were written, so nothing shown here can be a secret.
 *
 * The page describes an entry in its own language (AuditVocabulary): what was done, the kind of record, a yes or a no,
 * the browser. What was written (the action key, the field names and values, the label) is shown as it is.
 */
class AuditLogController extends Controller
{
    /**
     * The two ways the page shows the entries.
     *
     * @var array<int, string>
     */
    public const VIEWS = ['table', 'timeline'];

    /**
     * The ranges of days the page names (the browser turns them into the days of "from" and "to"); "all" is no range.
     *
     * @var array<int, string>
     */
    public const RANGES = ['today', 'yesterday', '7d', '30d'];

    /**
     * Create a new controller instance.
     */
    public function __construct(protected AuditLogInterface $auditLog) {}

    /**
     * The page. A link may open it on a view, an action, a kind of record, a search or a range of days
     * (?view=timeline&action=auth.login_failed&q=budi&range=7d, or &from=2026-11-01&to=2026-11-16).
     */
    public function index(Request $request): View
    {
        $isReady = AuditLog::ready();
        [$from, $to] = self::days($request);
        $range = $from && $to ? 'custom' : (in_array($request->query('range'), self::RANGES, true) ? (string) $request->query('range') : 'all');
        // An action named by a link that nothing has been recorded for yet is offered too.
        $action = self::text($request, 'action', 64);
        $actions = $isReady ? $this->auditLog->actions() : [];

        if ($action !== '' && ! in_array($action, $actions, true)) {
            $actions[] = $action;
        }

        return view('AuditLog::index', [
            'isReady' => $isReady,
            'actions' => AuditVocabulary::actionGroups($actions),
            'entities' => $isReady ? $this->entities() : [],
            'view' => in_array($request->query('view'), self::VIEWS, true) ? (string) $request->query('view') : 'table',
            'action' => $action,
            'entity' => self::text($request, 'entity', 64),
            'search' => self::text($request, 'q', 255),
            'range' => $range,
            'from' => $range === 'custom' ? $from->format('Y-m-d') : '',
            'to' => $range === 'custom' ? $to->format('Y-m-d') : '',
        ]);
    }

    /**
     * The entries for the table and Log activity (JSON).
     */
    public function list(Request $request): JsonResponse
    {
        if (! AuditLog::ready()) {
            return response()->json(['message' => __('The audit log is not switched on yet: its migration has not been run on this database.')], 503);
        }

        [$from, $to] = self::days($request);
        $entries = $this->auditLog->entries(
            self::text($request, 'search', 255),
            self::text($request, 'action', 64),
            self::text($request, 'entity', 64),
            $from,
            $to,
            Apps::tableParams($request, ['created_at'], 'created_at', 'desc')['per_page'],
        );

        return Apps::tableResponse($entries, fn (AuditLog $entry): array => $this->row($entry));
    }

    /**
     * One entry as the page shows it.
     *
     * @return array<string, mixed>
     */
    protected function row(AuditLog $entry): array
    {
        $old = is_array($entry->old_values) ? $entry->old_values : [];
        $new = is_array($entry->new_values) ? $entry->new_values : [];
        $changes = [];

        foreach (array_keys($new + $old) as $field) {
            $changes[] = [
                'field' => (string) $field,
                'old' => array_key_exists($field, $old) ? AuditLog::shown($old[$field]) : null,
                'new' => array_key_exists($field, $new) ? AuditLog::shown($new[$field]) : '-',
            ];
        }

        return [
            'id' => $entry->id,
            'at' => $entry->created_at?->format('d-m-Y H:i:s'),
            'ago' => $entry->created_at?->diffForHumans(),
            'user' => $entry->actorName(),
            'email' => $entry->user_email,
            'action' => $entry->action,
            'badge' => AuditVocabulary::badge((string) $entry->action),
            'activity' => AuditVocabulary::activity((string) $entry->action),
            'entity' => $entry->entityName(),
            'label' => $entry->entity_label,
            'changes' => $changes,
            'ip' => $entry->ip_address,
            // "Chrome 141 on Windows", from the User-Agent that was kept.
            'browser' => UserAgent::describe($entry->user_agent, __('on'), __('Command line')),
            // How it came in, e.g. the way a person signed in ("Microsoft SSO").
            'via' => $entry->via,
            'is_console' => $entry->user_agent === AuditLogger::CONSOLE,
        ];
    }

    /**
     * The kinds of record the trail has entries about, by the names the page gives them, in the order of those names.
     *
     * @return array<string, string> entity type => name
     */
    protected function entities(): array
    {
        return collect($this->auditLog->entityTypes())
            ->mapWithKeys(fn (string $type): array => [$type => AuditLog::entityTypeName($type)])
            ->sort()
            ->all();
    }

    /**
     * The first and the last day of the entries asked for ("from", "to": YYYY-MM-DD, days of the application's time
     * zone), either of them null; given the wrong way round, they are turned. Anything that is no day is none.
     *
     * @return array{0: ?CarbonImmutable, 1: ?CarbonImmutable}
     */
    protected static function days(Request $request): array
    {
        [$from, $to] = [self::day($request, 'from'), self::day($request, 'to')];

        return $from && $to && $from->gt($to) ? [$to, $from] : [$from, $to];
    }

    /**
     * One day of the request, or null.
     */
    protected static function day(Request $request, string $key): ?CarbonImmutable
    {
        $value = $request->input($key);

        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        $day = CarbonImmutable::createFromFormat('!Y-m-d', $value, config('app.timezone'));

        // (A day that does not exist, 2026-02-31, would be read as another one.)
        return $day instanceof CarbonImmutable && $day->format('Y-m-d') === $value ? $day : null;
    }

    /**
     * A parameter as one line of text: anything else (a list, ?q[]=...) counts as nothing.
     */
    protected static function text(Request $request, string $key, int $length): string
    {
        $value = $request->input($key);

        return is_string($value) ? trim(mb_substr($value, 0, $length)) : '';
    }
}
