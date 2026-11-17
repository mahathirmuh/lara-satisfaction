<?php

use Illuminate\Support\Facades\Route;
use Modules\SurveyManagement\Http\Controllers\QuestionController;
use Modules\SurveyManagement\Http\Controllers\ReminderController;
use Modules\SurveyManagement\Http\Controllers\SurveyController;

/*
| Menu group "Survey Management": Survey Period, Questions and Reminders
| (departments and sections are master data). The permission middleware maps GET => Read,
| POST => Create, PUT/PATCH => Update and DELETE => Delete on the matching menu.
*/
Route::middleware(['web', 'auth', 'permission'])->prefix('survey-management')->name('survey-management.')->group(function () {
    Route::prefix('survey')->name('survey.')->controller(SurveyController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('list', 'list')->name('list');
        Route::post('/', 'store')->name('store');
        Route::get('{survey}', 'show')->whereNumber('survey')->name('show');
        Route::put('{survey}', 'update')->whereNumber('survey')->name('update');
        Route::patch('{survey}/status', 'updateStatus')->whereNumber('survey')->name('status');
        Route::post('{survey}/duplicate', 'duplicate')->whereNumber('survey')->name('duplicate');
        Route::delete('{survey}', 'destroy')->whereNumber('survey')->name('destroy');
    });

    Route::prefix('question')->name('question.')->controller(QuestionController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('list', 'list')->name('list');
        Route::post('/', 'store')->name('store');
        Route::get('{question}', 'show')->whereNumber('question')->name('show');
        Route::put('{question}', 'update')->whereNumber('question')->name('update');
        Route::delete('{question}', 'destroy')->whereNumber('question')->name('destroy');
    });

    Route::prefix('reminder')->name('reminder.')->controller(ReminderController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('recipients', 'recipients')->name('recipients');
        Route::post('/', 'store')->name('store');
        Route::put('automatic', 'automatic')->name('automatic');
    });
});
