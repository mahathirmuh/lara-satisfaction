<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Switches the interface language: kept in the session, and on the account
 * of a signed-in user so it follows them to other devices and into their
 * notifications.
 */
class LocaleController extends Controller
{
    /**
     * Switch the interface language and go back to the page.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $locale = $request->validate([
            'locale' => ['required', 'string', Rule::in(array_keys(config('app.available_locales', [])))],
        ])['locale'];

        $request->session()->put(SetLocale::SESSION_KEY, $locale);
        $request->user()?->forceFill(['locale' => $locale])->saveQuietly();

        return redirect()->back();
    }
}
