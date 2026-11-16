<?php

namespace App\Http\Middleware;

use App\Helpers\Alert;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs out a user whose account was deactivated (or deleted) while a session
 * was still open.
 */
class EnsureUserIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && ! $user->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                abort(Response::HTTP_UNAUTHORIZED, __('Your account is inactive.'));
            }

            Alert::warning(__('Account inactive'), __('Your account has been deactivated. Please contact the IT Helpdesk.'));

            return redirect()->route('login');
        }

        return $next($request);
    }
}
