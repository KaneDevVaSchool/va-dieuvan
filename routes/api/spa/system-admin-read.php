<?php

/**
 * System Admin — đọc (dispatch.staff).
 */

use App\Http\Controllers\Api\System\MenuController;
use App\Http\Controllers\Api\System\PermissionMatrixController;
use App\Http\Controllers\Api\System\RoleAssignmentController;
use App\Http\Controllers\Api\System\SystemAuditController;
use App\Http\Controllers\Api\System\SystemDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/admin/system/dashboard', SystemDashboardController::class)
    ->middleware('throttle:60,1');

Route::get('/admin/permissions/matrix', [PermissionMatrixController::class, 'show'])
    ->middleware('throttle:60,1');

Route::prefix('admin/assignments')->group(function () {
    Route::get('/users', [RoleAssignmentController::class, 'index'])->middleware('throttle:60,1');
});

Route::prefix('admin/menu')->controller(MenuController::class)->group(function () {
    Route::get('/', 'index')->middleware('throttle:60,1');
});

Route::get('/audit-logs/{auditLog}/detail', [SystemAuditController::class, 'show'])
    ->middleware('throttle:60,1');
