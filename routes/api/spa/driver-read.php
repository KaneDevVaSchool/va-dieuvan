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
