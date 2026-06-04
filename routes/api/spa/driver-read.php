<?php

/**
 * Đọc: chỉ role tài xế (driver.spa) — canAccessDriverWebApp().
 */

use App\Http\Controllers\Api\Driver\DriverContextController;
use App\Http\Controllers\Api\Driver\DriverPolicyTripListController;
use App\Http\Controllers\Api\Driver\DriverPolicyTripStudentsController;
use App\Http\Controllers\Api\Driver\DriverTripController;
use App\Http\Controllers\Api\Driver\DriverTripPolicyStudentsController;
use Illuminate\Support\Facades\Route;

Route::get('/driver/summary', [DriverContextController::class, 'summary']);
Route::get('/driver/trips', [DriverTripController::class, 'history']);
Route::get('/driver/trips/{trip}/policy-students', [DriverTripPolicyStudentsController::class, 'index']);

// P2P — luồng tài xế (§5, §10.2)
Route::get('/driver/policy-trips', [DriverPolicyTripListController::class, 'index']);
Route::get('/driver/policy-trips/{policyTrip}/students', [DriverPolicyTripStudentsController::class, 'index']);

// Transport Program redesign (tp_*) — tài xế đọc
Route::get('/driver/tp-days', [\App\Http\Controllers\Api\Driver\DriverTpDayListController::class, 'index']);
Route::get('/driver/tp-days/{tpProgramDay}', [\App\Http\Controllers\Api\Driver\DriverTpDayDetailController::class, 'show']);
