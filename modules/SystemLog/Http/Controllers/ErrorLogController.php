<?php

namespace Modules\SystemLog\Http\Controllers;

use App\Helpers\Apps;
use App\Http\Controllers\Controller;
use App\Models\Log\LogError;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\SystemLog\Repositories\SystemLogInterface;

/**
 * Internal error monitoring (reported exceptions).
 */
class ErrorLogController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(protected SystemLogInterface $logs) {}

    /**
     * Error log page.
     */
    public function index(): View
    {
        return view('SystemLog::error.index');
    }

    /**
     * Error logs for the table (JSON).
     */
    public function list(Request $request): JsonResponse
    {
        $params = Apps::tableParams($request, ['created_at', 'exception'], 'created_at', 'desc');

        return Apps::tableResponse($this->logs->errorLogs($params), fn (LogError $log): array => [
            'id' => $log->id,
            'created_at' => $log->created_at?->toIso8601String(),
            'exception' => class_basename((string) $log->exception),
            'message' => Str::limit((string) $log->message, 160),
            'location' => basename((string) $log->file).':'.$log->line,
            'method' => $log->method,
        ]);
    }

    /**
     * One error log with its stack trace.
     */
    public function show(LogError $log): JsonResponse
    {
        return response()->json([
            'data' => [
                ...$log->only(['id', 'exception', 'message', 'file', 'line', 'url', 'method', 'user_id', 'stack_trace']),
                'created_at' => $log->created_at?->toIso8601String(),
                'data' => json_decode((string) $log->data, true) ?? $log->data,
            ],
        ]);
    }
}
