<?php

use Illuminate\Support\Facades\Route;
use Modules\MasterData\Http\Controllers\DepartmentController;
use Modules\MasterData\Http\Controllers\SectionController;

/*
| Menu group "Master Data": assessed departments and their sections (with
| the overview employees see before evaluating). The permission middleware
| maps GET => Read, POST => Create, PUT/PATCH => Update and DELETE => Delete
| on the matching menu.
*/
Route::middleware(['web', 'auth', 'permission'])->prefix('master-data')->name('master-data.')->group(function () {
    Route::prefix('department')->name('department.')->controller(DepartmentController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('list', 'list')->name('list');
        Route::post('/', 'store')->name('store');
        Route::get('{department}', 'show')->whereNumber('department')->name('show');
        Route::put('{department}', 'update')->whereNumber('department')->name('update');
        Route::delete('{department}', 'destroy')->whereNumber('department')->name('destroy');
    });

    Route::prefix('section')->name('section.')->controller(SectionController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('list', 'list')->name('list');
        Route::post('/', 'store')->name('store');
        Route::get('{section}', 'show')->whereNumber('section')->name('show');
        Route::put('{section}', 'update')->whereNumber('section')->name('update');
        Route::delete('{section}', 'destroy')->whereNumber('section')->name('destroy');
    });
});
