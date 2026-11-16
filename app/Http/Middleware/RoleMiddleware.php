<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route to users holding one of the given role names,
 * e.g. ->middleware('role:Super Admin,Manager').
 */
class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_unless($user !== null && ($user->isItAdmin() || $user->hasAnyRole(...$roles)), Response::HTTP_FORBIDDEN);

        return $next($request);
    }
}
