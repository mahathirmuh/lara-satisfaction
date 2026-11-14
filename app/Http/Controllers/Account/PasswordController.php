<?php

namespace App\Http\Controllers\Account;

use App\Helpers\Alert;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Lets a signed-in user change the password of their Merdeka account.
 */
class PasswordController extends Controller
{
    /**
     * Show the change password form.
     */
    public function edit(): View
    {
        return view('account.password');
    }

    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults(), 'different:current_password'],
        ]);

        $request->user()->update([
            'password' => $validated['password'],
        ]);

        Alert::success('Password updated', 'Your password has been changed.');

        return redirect()->route('account.password.edit');
    }
}
