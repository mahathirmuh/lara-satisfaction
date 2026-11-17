<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\SsoController;
use App\Http\Controllers\ChangelogController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserGuideController;
use App\Http\Controllers\UserPhotoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Application-wide routes (landing page, authentication, notifications).
| Sign in is SSO or the network account (LDAP) only: there are no local
| passwords, so there is no password page either.
| Module routes live in modules/{Module}/Routes/web and are loaded by each
| module's service provider.
|
*/

Route::get('/', LandingController::class)->name('landing');

/** Interface language (signed in or not) */
Route::post('locale', LocaleController::class)->name('locale.update');

/** Authentication */
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->name('login.store');

    Route::get('auth/{provider}/redirect', [SsoController::class, 'redirect'])
        ->whereIn('provider', array_keys(SsoController::DRIVERS))
        ->name('sso.redirect');
    Route::get('auth/{provider}/callback', [SsoController::class, 'callback'])
        ->whereIn('provider', array_keys(SsoController::DRIVERS))
        ->name('sso.callback');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    /** The signed-in user's own profile */
    Route::get('profile', ProfileController::class)->name('profile.show');

    /** What changed in each version (the version in the footer opens it) */
    Route::get('changelog', ChangelogController::class)->name('changelog');

    /** The user guide (PDF) in the language of the page, from the user menu */
    Route::get('user-guide', UserGuideController::class)->name('user-guide');

    /** Employee photos (own, or everyone's for user management and survey progress) */
    Route::get('users/{user}/photo', UserPhotoController::class)->whereNumber('user')->name('users.photo');

    /** In-app notifications (own notifications only) */
    Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::get('notifications/{notification}', [NotificationController::class, 'show'])
        ->whereUuid('notification')
        ->name('notifications.show');
});
