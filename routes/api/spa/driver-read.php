<?php

/**
 * Đọc: chỉ role tài xế (driver.spa) — canAccessDriverWebApp().
 */

use App\Http\Controllers\Api\Driver\DriverContextController;
use App\Http\Controllers\Api\Driver\DriverMaintenanceController;
use App\Http\Controllers\Api\Driver\DriverTripController;
use Illuminate\Support\Facades\Route;

Route::get('/driver/summary', [DriverContextController::class, 'summary']);
Route::get('/driver/trips', [DriverTripController::class, 'history']);
Route::get('/driver/maintenance', [DriverMaintenanceController::class, 'index']);
Route::get('/driver/maintenance/{item}', [DriverMaintenanceController::class, 'show'])->whereNumber('item');
