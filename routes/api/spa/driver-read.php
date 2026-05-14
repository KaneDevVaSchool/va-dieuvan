<?php

/**
 * Đọc: chỉ role tài xế (driver.spa) — canAccessDriverWebApp().
 */

use App\Http\Controllers\Api\Driver\DriverContextController;
use App\Http\Controllers\Api\Driver\DriverTripController;
use App\Http\Controllers\Api\Driver\DriverTripPolicyStudentsController;
use Illuminate\Support\Facades\Route;

Route::get('/driver/summary', [DriverContextController::class, 'summary']);
Route::get('/driver/trips', [DriverTripController::class, 'history']);
Route::get('/driver/trips/{trip}/policy-students', [DriverTripPolicyStudentsController::class, 'index']);
