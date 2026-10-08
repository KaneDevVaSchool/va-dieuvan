<?php

/**
 * Đọc: chỉ role tài xế (driver.spa) — canAccessDriverWebApp().
 */

use App\Http\Controllers\Api\Driver\DriverContextController;
use App\Http\Controllers\Api\Driver\DriverCostVehicleController;
use App\Http\Controllers\Api\Driver\DriverTripController;
use Illuminate\Support\Facades\Route;

Route::get('/driver/summary', [DriverContextController::class, 'summary']);
Route::get('/driver/cost-vehicles', [DriverCostVehicleController::class, 'index']);
Route::get('/driver/trips', [DriverTripController::class, 'history']);

// Transport Program redesign (tp_*) — tài xế đọc
Route::get('/driver/tp-days', [\App\Http\Controllers\Api\Driver\DriverTpDayListController::class, 'index']);
Route::get('/driver/tp-days/{tpProgramDay}', [\App\Http\Controllers\Api\Driver\DriverTpDayDetailController::class, 'show']);

// Checklist Daily/Weekly — tài xế đọc
Route::prefix('driver/checklists')
    ->middleware('feature:module.driver_checklist')
    ->controller(\App\Http\Controllers\Api\Driver\DriverChecklistController::class)
    ->group(function () {
        Route::get('/overview', 'overview');
        Route::get('/options', 'options');
        Route::get('/', 'index');
        Route::get('/{checklist}', 'show')->whereNumber('checklist');
        Route::get('/{checklist}/pdf', 'pdf')->whereNumber('checklist')->middleware('throttle:30,1');
    });

// Hồ sơ đề xuất / thanh toán chi phí xe — người lập đọc
Route::prefix('driver/expenses')
    ->middleware('feature:module.driver_expense')
    ->controller(\App\Http\Controllers\Api\Driver\DriverExpenseController::class)
    ->group(function () {
        Route::get('/defaults', 'defaults');
        Route::get('/cost-candidates', 'costCandidates');
        Route::get('/', 'index');
        Route::get('/{expense}', 'show')->whereNumber('expense');
        Route::get('/{expense}/pdf', 'pdf')->whereNumber('expense')->middleware('throttle:30,1');
        Route::get('/{expense}/xlsx', 'xlsx')->whereNumber('expense')->middleware('throttle:30,1');
    });
