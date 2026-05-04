<?php

/**
 * Đọc: superadmin, admin, dispatcher, driver (cùng ở SPA sau dispatch.web).
 */

use App\Http\Controllers\Api\Attachments\AttachmentController;
use App\Http\Controllers\Api\Cargo\CargoController;
use App\Http\Controllers\Api\Costs\TripCostController;
use App\Http\Controllers\Api\D2D\RouteController;
use App\Http\Controllers\Api\NavBadgesController;
use App\Http\Controllers\Api\Notifications\InboxController;
use App\Http\Controllers\Api\PushSubscriptionController;
use App\Http\Controllers\Api\Requests\DispatchRequestController;
use App\Http\Controllers\Api\Trips\TripController;
use App\Http\Controllers\Api\UserProfileController;
use Illuminate\Support\Facades\Route;

// Lightweight: không LogApiActivity
Route::get('/user', [UserProfileController::class, 'show']);
Route::get('/push/vapid-public-key', [PushSubscriptionController::class, 'vapidPublicKey'])
    ->middleware('throttle:30,1');
Route::post('/push/subscriptions', [PushSubscriptionController::class, 'store'])->middleware('throttle:20,1');
Route::delete('/push/subscriptions', [PushSubscriptionController::class, 'destroy'])->middleware('throttle:20,1');

Route::get('/notifications/inbox', [InboxController::class, 'index']);
Route::post('/notifications/read-all', [InboxController::class, 'markAllRead']);
Route::post('/notifications/{notification}/read', [InboxController::class, 'markRead'])
    ->whereUuid('notification');

Route::get('/dispatch-requests/{dispatchRequest}', [DispatchRequestController::class, 'show']);

Route::controller(TripController::class)->group(function () {
    Route::get('/trips', 'index');
    Route::get('/trips/stats', 'stats');
    Route::get('/trips/{trip}', 'show');
});

Route::get('/trip-costs', [TripCostController::class, 'index']);
Route::get('/trip-costs/{tripCost}', [TripCostController::class, 'show']);
Route::get('/trips/{trip}/costs', [TripCostController::class, 'costsForTrip']);

Route::controller(CargoController::class)->group(function () {
    Route::get('/cargo-shipments', 'index');
    Route::get('/cargo-shipments/{cargoShipment}/timeline', 'timeline');
    Route::get('/cargo-shipments/{cargoShipment}', 'show');
});

Route::controller(RouteController::class)->group(function () {
    Route::get('/routes', 'index');
    Route::get('/routes/{route}', 'show');
});

Route::get('/nav/badges', NavBadgesController::class)->middleware('throttle:60,1');

// Đọc tệp: dùng chung (gắn kèm tài liệu chuyến, O-POD) — tách throttle giống khối staff
Route::prefix('attachments')->controller(AttachmentController::class)->group(function () {
    Route::get('/{attachment}/download', 'download')
        ->whereNumber('attachment')
        ->middleware('throttle:120,1');
});
