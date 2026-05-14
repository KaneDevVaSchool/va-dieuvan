<?php

/**
 * Ghi: tất cả user SPA có dispatch.web — hồ sơ + một số phiếu (portal nội bộ).
 */

use App\Http\Controllers\Api\Requests\DispatchRequestController;
use App\Http\Controllers\Api\UserProfileController;
use Illuminate\Support\Facades\Route;

Route::patch('/user', [UserProfileController::class, 'update']);

Route::post('/dispatch-requests', [DispatchRequestController::class, 'store'])
    ->middleware(['throttle:20,1', 'idempotency', 'permission:request.create'])
    ->name('api.dispatch-requests.store');

Route::patch('/dispatch-requests/{dispatchRequest}/passenger-count', [DispatchRequestController::class, 'patchRecurringPassengerCount'])
    ->middleware(['throttle:60,1', 'permission:any,request.update_own,trip.view_all'])
    ->name('api.dispatch-requests.patch-passenger-count');
