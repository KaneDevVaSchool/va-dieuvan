<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\ClientTelemetryController;
use App\Http\Controllers\Api\Portal\PortalDispatchRequestController;
use App\Http\Controllers\Api\UserProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:20,1');
Route::post('/telemetry/frontend', [ClientTelemetryController::class, 'store'])->middleware('throttle:60,1');

/*
| Sanctum — phiên đăng nhập
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('throttle:30,1');

    Route::middleware(['throttle:120,1'])->group(function () {
        Route::get('/user', [UserProfileController::class, 'show']);
    });

    Route::middleware([\App\Http\Middleware\LogApiActivity::class, 'throttle:180,1'])->group(function () {
        Route::post('/portal/dispatch-requests', [PortalDispatchRequestController::class, 'store'])
            ->middleware('idempotency');
    });

    /*
    | Web SPA: điều vận (admin, dispatcher) hoặc tài xế (driver) — dispatch.web
    |--------------------------------------------------------------------------
    | dispatch.staff: chỉ superadmin, admin, dispatcher, department_head (không phải tài khoản chỉ driver)
    | driver.spa: chỉ role tài xế
    |--------------------------------------------------------------------------
    */
    Route::middleware(['dispatch.web'])->group(function () {
        // Đọc (nhẹ, không ghi log hoạt động từng request)
        Route::middleware(['throttle:120,1'])->group(function () {
            require base_path('routes/api/spa/common-read.php');
        });

        Route::middleware(['driver.spa', 'throttle:120,1'])->group(function () {
            require base_path('routes/api/spa/driver-read.php');
        });

        Route::middleware(['dispatch.staff', 'throttle:120,1'])->group(function () {
            require base_path('routes/api/spa/dispatch-staff-read.php');
        });

        // Ghi: log hoạt động + throttle (common + luồng tài xế)
        Route::middleware([\App\Http\Middleware\LogApiActivity::class, 'throttle:180,1'])->group(function () {
            require base_path('routes/api/spa/common-mutate.php');
            require base_path('routes/api/spa/driver-mutate.php');
        });

        Route::middleware(['dispatch.staff', \App\Http\Middleware\LogApiActivity::class, 'throttle:180,1'])->group(function () {
            require base_path('routes/api/spa/dispatch-staff-mutate.php');
        });
    });
});
