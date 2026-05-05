<?php

/**
 * Ghi: luồng tài xế (trạng thái chuyến, sự kiện, sổ nhật ký, chi phí, tệp).
 * Phân quyền theo FormRequest (dispatcher vẫn dùng được nếu có quyền tương ứng).
 */

use App\Http\Controllers\Api\Attachments\AttachmentController;
use App\Http\Controllers\Api\Costs\TripCostController;
use App\Http\Controllers\Api\Trips\TripOpsController;
use Illuminate\Support\Facades\Route;

Route::prefix('trips')->group(function () {
    Route::controller(TripOpsController::class)->group(function () {
        Route::post('/{trip}/status', 'updateStatus')->middleware('throttle:30,1');
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

Route::prefix('attachments')->controller(AttachmentController::class)->group(function () {
    Route::post('/', 'upload')->middleware('throttle:30,1');
    Route::delete('/{attachment}', 'destroy')
        ->whereNumber('attachment')
        ->middleware('throttle:30,1');
    Route::post('/{attachment}/ocr', 'runOcr')->middleware('throttle:15,1');
});
