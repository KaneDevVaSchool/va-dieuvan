<?php

/**
 * Ghi: luồng tài xế (trạng thái chuyến, sự kiện, sổ nhật ký, chi phí, tệp).
 * Phân quyền theo FormRequest (dispatcher vẫn dùng được nếu có quyền tương ứng).
 */

use App\Http\Controllers\Api\Attachments\AttachmentController;
use App\Http\Controllers\Api\Costs\TripCostController;
use App\Http\Controllers\Api\Driver\DriverPolicyTripCompleteController;
use App\Http\Controllers\Api\Driver\DriverPolicyTripStartController;
use App\Http\Controllers\Api\Driver\DriverPolicyTripStudentAlightController;
use App\Http\Controllers\Api\Driver\DriverPolicyTripStudentBoardController;
use App\Http\Controllers\Api\Driver\PolicyTripStudentAbsentController;
use App\Http\Controllers\Api\Trips\TripOpsController;
use Illuminate\Support\Facades\Route;

Route::prefix('trips')->group(function () {
    Route::controller(TripOpsController::class)->group(function () {
        Route::post('/{trip}/status', 'updateStatus')->middleware('throttle:120,1');
        Route::post('/{trip}/events', 'addEvent')->middleware('throttle:60,1');
        Route::put('/{trip}/record', 'upsertRecord')->middleware('throttle:30,1');
    });

    Route::controller(TripCostController::class)->group(function () {
        Route::post('/{trip}/costs', 'store')
            ->middleware(['throttle:60,1', 'idempotency'])
            ->name('api.trips.costs.store');
        Route::post('/{trip}/costs/{tripCost}/receipt', 'uploadReceiptForTrip')->middleware('throttle:30,1');
    });
});

Route::prefix('trip-costs')->controller(TripCostController::class)->group(function () {
    Route::patch('/{tripCost}', 'updateByDriver')->middleware('throttle:30,1');
    Route::delete('/{tripCost}', 'destroyByDriver')->middleware('throttle:20,1');
});

// P2P — luồng tài xế điểm danh (§5, §10.2)
Route::prefix('driver/policy-trips')->group(function () {
    Route::post('/{policyTrip}/start', DriverPolicyTripStartController::class)->middleware('throttle:60,1');
    Route::post('/{policyTrip}/complete', DriverPolicyTripCompleteController::class)->middleware('throttle:60,1');
});

Route::prefix('driver/policy-trip-students')->group(function () {
    Route::patch('/{policyTripStudent}/board', DriverPolicyTripStudentBoardController::class)->middleware('throttle:120,1');
    Route::patch('/{policyTripStudent}/alight', DriverPolicyTripStudentAlightController::class)->middleware('throttle:120,1');
    Route::patch('/{policyTripStudent}/absent', PolicyTripStudentAbsentController::class)->middleware('throttle:60,1');
});

Route::prefix('attachments')->controller(AttachmentController::class)->group(function () {
    Route::post('/', 'upload')->middleware('throttle:30,1');
    Route::delete('/{attachment}', 'destroy')
        ->whereNumber('attachment')
        ->middleware('throttle:30,1');
    Route::post('/{attachment}/ocr', 'runOcr')->middleware('throttle:attachment-ocr');
});
