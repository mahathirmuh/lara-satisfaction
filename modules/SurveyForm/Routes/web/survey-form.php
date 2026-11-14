<?php

use Illuminate\Support\Facades\Route;
use Modules\SurveyForm\Http\Controllers\SurveyFormController;

/*
| Menu "My Survey" (survey-form.index). The permission middleware requires
| Read for viewing a survey and Create for saving a draft or submitting.
*/
Route::middleware(['web', 'auth', 'permission'])->prefix('survey-form')->name('survey-form.')->group(function () {
    Route::get('/', [SurveyFormController::class, 'index'])->name('index');
    Route::get('{survey}', [SurveyFormController::class, 'show'])->name('show');
    Route::post('{survey}', [SurveyFormController::class, 'store'])->name('store');
    Route::post('{survey}/draft', [SurveyFormController::class, 'saveDraft'])->name('draft');
    Route::get('{survey}/thank-you', [SurveyFormController::class, 'thanks'])->name('thanks');
});
