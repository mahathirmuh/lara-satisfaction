<?php

namespace Modules\SystemLog\Http\Controllers;

use App\Helpers\Apps;
use App\Http\Controllers\Controller;
use App\Models\Log\LogActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\SystemLog\Repositories\SystemLogInterface;

/**
 * Audit trail of state-changing requests.
 */
class ActivityLogController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(protected SystemLogInterface $logs) {}

    /**
     * Activity log page.
     */
    public function index(): View
    {
        return view('SystemLog::activity.index');
    }

    /**
     * Activity logs for the table (JSON).
     */
    public function list(Request $request): JsonResponse
    {
        $params = Apps::tableParams($request, ['created_at', 'method', 'route_name', 'status_code'], 'created_at', 'desc');

        $logs = $this->logs->activityLogs($params, [
            'method' => in_array($request->query('method'), ['POST', 'PUT', 'PATCH', 'DELETE'], true) ? $request->query('method') : null,
        ]);

        return Apps::tableResponse($logs, fn (LogActivity $log): array => [
            'id' => $log->id,
            'created_at' => $log->created_at?->toIso8601String(),
            'user' => $log->user?->name ?? 'Guest',
            'method' => $log->method,
            'route_name' => $log->route_name,
            'url' => $log->url,
            'status_code' => $log->status_code,
            'ip' => $log->ip,
        ]);
    }

    /**
     * One activity log with its (redacted) payload.
     */
    public function show(LogActivity $log): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $log->id,
                'created_at' => $log->created_at?->toIso8601String(),
                'user' => $log->user?->only(['id', 'name', 'email']),
                'method' => $log->method,
                'route_name' => $log->route_name,
                'url' => $log->url,
                'status_code' => $log->status_code,
                'ip' => $log->ip,
                'agent' => $log->agent,
                'data' => json_decode((string) $log->data, true) ?? $log->data,
            ],
        ]);
    }
}
