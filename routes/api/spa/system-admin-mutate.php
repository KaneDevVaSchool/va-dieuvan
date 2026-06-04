<?php

/**
 * System Admin — ghi (dispatch.staff).
 */

use App\Http\Controllers\Api\System\MenuController;
use App\Http\Controllers\Api\System\PermissionMatrixController;
use App\Http\Controllers\Api\System\RoleAssignmentController;
use Illuminate\Support\Facades\Route;

Route::post('/admin/permissions/sync', [PermissionMatrixController::class, 'sync'])
    ->middleware('throttle:30,1');

Route::post('/admin/assignments', [RoleAssignmentController::class, 'store'])
    ->middleware('throttle:30,1');

Route::prefix('admin/menu')->controller(MenuController::class)->group(function () {
    Route::patch('/reorder', 'reorder')->middleware('throttle:30,1');
    Route::post('/', 'store')->middleware('throttle:30,1');
    Route::patch('/{menuItem}', 'update')->middleware('throttle:30,1');
    Route::delete('/{menuItem}', 'destroy')->middleware('throttle:30,1');
});
