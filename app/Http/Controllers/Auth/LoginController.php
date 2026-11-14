<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\Alert;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Show the Merdeka sign in page (SSO + Merdeka account).
     */
    public function create(): View
    {
        return view('auth.login', [
            'ssoProviders' => SsoController::loginProviders(),
        ]);
    }

    /**
     * Handle a local (email / Employee ID) sign in.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();
        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        Alert::success('Welcome!', 'Signed in as '.$user->name.'.');

        return redirect()->intended($user->homeUrl());
    }

    /**
     * Sign the user out.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Alert::success('Logout Success', 'Good Bye!');

        return redirect()->route('login');
    }
}
