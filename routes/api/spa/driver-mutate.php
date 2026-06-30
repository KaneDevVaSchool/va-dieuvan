<?php

/**
 * Ghi: luồng tài xế (trạng thái chuyến, sự kiện, sổ nhật ký, chi phí, tệp).
 * Phân quyền theo FormRequest (dispatcher vẫn dùng được nếu có quyền tương ứng).
 */

use App\Http\Controllers\Api\Attachments\AttachmentController;
use App\Http\Controllers\Api\Costs\TripCostController;
use App\Http\Controllers\Api\Driver\DriverCargoController;
use App\Http\Controllers\Api\Trips\TripOpsController;
use Illuminate\Support\Facades\Route;

Route::prefix('trips')->group(function () {
    Route::controller(TripOpsController::class)->group(function () {
        Route::post('/{trip}/status', 'updateStatus')->middleware('throttle:120,1');
        Route::post('/{trip}/events', 'addEvent')->middleware('throttle:60,1');
        Route::delete('/{trip}/events/{tripEvent}', 'deleteEvent')->middleware('throttle:60,1');
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
    Route::post('/', 'storeStandalone')
        ->middleware(['throttle:60,1', 'idempotency'])
        ->name('api.trip-costs.store');
    Route::patch('/{tripCost}', 'updateByDriver')->middleware('throttle:30,1');
    Route::delete('/{tripCost}', 'destroyByDriver')->middleware('throttle:20,1');
    Route::post('/bulk-delete', 'bulkDestroy')->middleware('throttle:20,1');
});

// Transport Program redesign (tp_*) — tài xế ghi (§5, §7)
Route::prefix('driver/tp-days')->group(function () {
    Route::post('/{tpProgramDay}/confirm', [\App\Http\Controllers\Api\Driver\DriverTpDayConfirmController::class, 'confirm'])->middleware('throttle:60,1');
    Route::delete('/{tpProgramDay}/confirm', [\App\Http\Controllers\Api\Driver\DriverTpDayConfirmController::class, 'unconfirm'])->middleware('throttle:60,1');
    Route::post('/{tpProgramDay}/report-busy', [\App\Http\Controllers\Api\Driver\DriverTpDayConfirmController::class, 'reportBusy'])->middleware('throttle:30,1');
    Route::post('/{tpProgramDay}/start', \App\Http\Controllers\Api\Driver\DriverTripStartController::class)->middleware('throttle:60,1');
});

// Hàng hoá: tài xế ghi nhận nhận/giao + ảnh minh chứng cho chuyến mình được phân.
Route::prefix('driver/cargo-shipments')->controller(DriverCargoController::class)->group(function () {
    Route::post('/{cargoShipment}/status', 'updateStatus')->middleware('throttle:60,1');
    Route::post('/{cargoShipment}/pod', 'uploadPod')->middleware('throttle:20,1');
});

Route::prefix('driver/tp-executions')->group(function () {
    Route::post('/{tpTripExecution}/complete', [\App\Http\Controllers\Api\Driver\DriverTripCompleteController::class, 'complete'])->middleware('throttle:60,1');
    Route::post('/{tpTripExecution}/sync', \App\Http\Controllers\Api\Driver\DriverTripSyncController::class)->middleware('throttle:60,1');
    Route::patch('/{tpTripExecution}/students/{student}/board', [\App\Http\Controllers\Api\Driver\DriverTpStudentActionController::class, 'board'])->middleware('throttle:120,1');
    Route::patch('/{tpTripExecution}/students/{student}/alight', [\App\Http\Controllers\Api\Driver\DriverTpStudentActionController::class, 'alight'])->middleware('throttle:120,1');
    Route::patch('/{tpTripExecution}/students/{student}/absent', [\App\Http\Controllers\Api\Driver\DriverTpStudentActionController::class, 'absent'])->middleware('throttle:60,1');
    Route::patch('/{tpTripExecution}/students/{student}/undo-absent', [\App\Http\Controllers\Api\Driver\DriverTpStudentActionController::class, 'undoAbsent'])->middleware('throttle:60,1');
    Route::patch('/{tpTripExecution}/students/{student}/notes', [\App\Http\Controllers\Api\Driver\DriverTpStudentActionController::class, 'updateNotes'])->middleware('throttle:60,1');
});

Route::prefix('attachments')->controller(AttachmentController::class)->group(function () {
    Route::post('/', 'upload')->middleware('throttle:30,1');
    Route::delete('/{attachment}', 'destroy')
        ->whereNumber('attachment')
        ->middleware('throttle:30,1');
    Route::post('/{attachment}/ocr', 'runOcr')->middleware('throttle:attachment-ocr');
});
