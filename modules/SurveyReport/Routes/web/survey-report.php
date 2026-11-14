<?php

use Illuminate\Support\Facades\Route;
use Modules\SurveyReport\Http\Controllers\SurveyReportController;

/*
| Menu "Survey Report" (survey-report.index). Every data endpoint requires
| the Read permission on the menu.
*/
Route::middleware(['web', 'auth', 'permission'])->prefix('survey-report')->name('survey-report.')->group(function () {
    Route::get('/', [SurveyReportController::class, 'index'])->name('index');
    Route::get('{survey}', [SurveyReportController::class, 'show'])->name('show');
    Route::get('{survey}/comments', [SurveyReportController::class, 'comments'])->name('comments');
    Route::get('{survey}/export', [SurveyReportController::class, 'export'])->name('export');
});
