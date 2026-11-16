<?php

use Illuminate\Support\Facades\Route;
use Modules\AuditLog\Http\Controllers\AuditLogController;

/*
| The audit trail (menu "Audit Log"). Read only: there is no route that changes or deletes an entry. The route names
| follow "<menu key>.<action>", so the permission middleware maps them to the menu: opening the page needs access to
| it, loading the entries needs Read.
*/
Route::middleware(['web', 'auth', 'permission'])->prefix('audit-log')->name('audit-log.')
    ->controller(AuditLogController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('list', 'list')->name('list');
    });
