<?php

namespace App\Http\Middleware;

use App\Helpers\LogApps;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Writes every state-changing web request (non GET/HEAD/OPTIONS) to the
 * activity log after the response has been produced. Sensitive values and
 * anonymous survey payloads are redacted by LogApps.
 */
class LogActivityMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $userIdBefore = $request->user()?->getAuthIdentifier();

        $response = $next($request);

        if (! config('merdeka.log.activity') || $request->isMethodSafe()) {
            return $response;
        }

        try {
            // After a login the new user is known; after a logout only the previous one.
            LogApps::activityLog($request, $response, $request->user()?->getAuthIdentifier() ?? $userIdBefore);
        } catch (Throwable $exception) {
            report($exception);
        }

        return $response;
    }
}
