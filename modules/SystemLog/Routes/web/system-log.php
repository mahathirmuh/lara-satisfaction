<?php

use Illuminate\Support\Facades\Route;
use Modules\SystemLog\Http\Controllers\ActivityLogController;
use Modules\SystemLog\Http\Controllers\ErrorLogController;

/*
| Menu group "System Log" (read only): activity and error monitoring.
*/
Route::middleware(['web', 'auth', 'permission'])->prefix('system-log')->name('system-log.')->group(function () {
    Route::prefix('activity')->name('activity.')->controller(ActivityLogController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('list', 'list')->name('list');
        Route::get('{log}', 'show')->whereNumber('log')->name('show');
    });

    Route::prefix('error')->name('error.')->controller(ErrorLogController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('list', 'list')->name('list');
        Route::get('{log}', 'show')->whereNumber('log')->name('show');
    });
});
