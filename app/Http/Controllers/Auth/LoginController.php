<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\Alert;
use App\Http\Controllers\Controller;
use App\Http\Middleware\SetLocale;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Auth\CompanyAccounts;
use App\Services\Auth\LdapAuthenticator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Show the Merdeka sign in page: SSO and the network account (LDAP) only.
     */
    public function create(LdapAuthenticator $directory): View
    {
        return view('auth.login', [
            'ssoProviders' => SsoController::loginProviders(),
            'isNetworkSignInConfigured' => $directory->isConfigured(),
        ]);
    }

    /**
     * Handle a network account (LDAP) sign in.
     */
    public function store(LoginRequest $request, LdapAuthenticator $directory, CompanyAccounts $accounts): RedirectResponse
    {
        $request->authenticate($directory, $accounts);

        $request->session()->regenerate();

        $user = $request->user();
        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        Alert::success(__('Welcome!'), __('Signed in as :name.', ['name' => $user->name]));

        return redirect()->intended($user->homeUrl());
    }

    /**
     * Sign the user out.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $locale = app()->getLocale();

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // The sign-in page stays in the language the person was using.
        $request->session()->put(SetLocale::SESSION_KEY, $locale);

        Alert::success(__('Logout Success'), __('Good Bye!'));

        return redirect()->route('login');
    }
}
