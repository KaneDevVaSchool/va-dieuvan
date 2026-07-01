<?php

/**
 * System Admin — ghi (dispatch.staff).
 */

use App\Http\Controllers\Api\System\TrashController;
use Illuminate\Support\Facades\Route;

Route::prefix('trash')->controller(TrashController::class)->group(function () {
    Route::post('/restore', 'restore')->middleware('throttle:60,1');
    Route::post('/force-delete', 'forceDelete')->middleware('throttle:60,1');
});
