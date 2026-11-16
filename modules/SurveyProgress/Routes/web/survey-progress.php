<?php

use Illuminate\Support\Facades\Route;
use Modules\SurveyProgress\Http\Controllers\SurveyProgressController;

/*
| Menu "Survey Progress": where every employee stands with a survey (Not
| Started, In Progress, Completed). The permission middleware maps GET to Read.
*/
Route::middleware(['web', 'auth', 'permission'])->prefix('survey-progress')->name('survey-progress.')->group(function () {
    Route::get('/', [SurveyProgressController::class, 'index'])->name('index');
    Route::get('list', [SurveyProgressController::class, 'list'])->name('list');
});
