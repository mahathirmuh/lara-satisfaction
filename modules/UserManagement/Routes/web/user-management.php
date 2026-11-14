<?php

use Illuminate\Support\Facades\Route;
use Modules\UserManagement\Http\Controllers\RolePermissionController;
use Modules\UserManagement\Http\Controllers\UserRegisteredController;

Route::middleware(['web', 'auth', 'permission'])->prefix('user-management')->name('user-management.')->group(function () {
    Route::prefix('user-registered')->name('user-registered.')->controller(UserRegisteredController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('list', 'list')->name('list');
        Route::post('/', 'store')->name('store');
        Route::get('{user}', 'show')->whereNumber('user')->name('show');
        Route::put('{user}', 'update')->whereNumber('user')->name('update');
        Route::delete('{user}', 'destroy')->whereNumber('user')->name('destroy');
    });

    Route::prefix('role-permission')->name('role-permission.')->controller(RolePermissionController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('list', 'list')->name('list');
        Route::post('/', 'store')->name('store');
        Route::get('{role}', 'show')->whereNumber('role')->name('show');
        Route::put('{role}', 'update')->whereNumber('role')->name('update');
        Route::delete('{role}', 'destroy')->whereNumber('role')->name('destroy');
    });
});
