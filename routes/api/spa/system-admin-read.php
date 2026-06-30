<?php

/**
 * System Admin — đọc (dispatch.staff).
 */

use App\Http\Controllers\Api\System\SystemAuditController;
use App\Http\Controllers\Api\System\TrashController;
use Illuminate\Support\Facades\Route;

Route::get('/audit-logs/{auditLog}/detail', [SystemAuditController::class, 'show'])
    ->middleware('throttle:60,1');

Route::prefix('trash')->controller(TrashController::class)->group(function () {
    Route::get('/summary', 'summary')->middleware('throttle:60,1');
    Route::get('/', 'index')->middleware('throttle:60,1');
});
