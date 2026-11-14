<?php

namespace App\Helpers;

use App\Models\Log\LogActivity;
use App\Models\Log\LogError;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Activity and error logging to the database (Merdeka internal monitoring).
 */
class LogApps
{
    /**
     * Placeholder stored instead of sensitive values.
     */
    public const REDACTED = '[REDACTED]';

    /**
     * Record a state-changing request in `activity_logs`.
     */
    public static function activityLog(Request $request, ?Response $response = null, int|string|null $userId = null): void
    {
        LogActivity::create([
            'subject' => config('merdeka.app_name'),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'route_name' => $request->route()?->getName(),
            'status_code' => $response?->getStatusCode(),
            'data' => static::redactedPayload($request),
            'agent' => Str::limit((string) $request->userAgent(), 1000, ''),
            'ip' => $request->ip(),
            'user_id' => $userId ?? $request->user()?->getAuthIdentifier(),
        ]);
    }

    /**
     * Record a reported exception in `error_logs`. Failures are swallowed so
     * error logging can never break the default exception reporting.
     */
    public static function errorLog(Throwable $exception, ?Request $request = null): void
    {
        try {
            LogError::create([
                'subject' => config('merdeka.app_name'),
                'exception' => $exception::class,
                'message' => Str::limit($exception->getMessage(), 60000, ''),
                'stack_trace' => $exception->getTraceAsString(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'url' => $request?->fullUrl(),
                'method' => $request?->method(),
                'data' => $request ? static::redactedPayload($request) : null,
                'user_id' => $request?->user()?->getAuthIdentifier(),
            ]);
        } catch (Throwable) {
            // The database may be the reason of the failure; rely on the log file.
        }
    }

    /**
     * Request payload as JSON with secrets and anonymous answers removed.
     */
    public static function redactedPayload(Request $request): string
    {
        $routeName = $request->route()?->getName();

        if ($routeName !== null && in_array($routeName, config('merdeka.log.redacted_routes', []), true)) {
            return (string) json_encode(['_redacted' => 'Payload not stored (anonymous survey submission).']);
        }

        return (string) json_encode(
            static::redact($request->except(array_keys($request->allFiles()))),
            JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR
        );
    }

    /**
     * Recursively replace sensitive keys with a placeholder.
     *
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    public static function redact(array $data): array
    {
        $sensitiveKeys = array_map('strtolower', config('merdeka.log.redacted_keys', []));

        foreach ($data as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), $sensitiveKeys, true)) {
                $data[$key] = static::REDACTED;

                continue;
            }

            if (is_array($value)) {
                $data[$key] = static::redact($value);
            }
        }

        return Arr::where($data, fn ($value): bool => ! is_resource($value));
    }
}
