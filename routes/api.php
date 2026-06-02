<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\ClientTelemetryController;
use App\Http\Controllers\Api\Portal\PortalDispatchRequestController;
use App\Http\Controllers\Api\Portal\PortalFormTemplateController;
use App\Http\Controllers\Api\Portal\PortalNotificationController;
use App\Http\Controllers\Api\Requests\DispatchRequestController;
use App\Http\Controllers\Api\Requests\DispatchRequestTemplateController;
use App\Http\Controllers\Api\UserProfileController;
use App\Http\Controllers\Api\UserSearchForDispatchFormController;
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

    Route::middleware(['throttle:120,1'])->group(function () {
        Route::get('/portal/dispatch-requests/summary', [PortalDispatchRequestController::class, 'summary']);
        Route::get('/portal/dispatch-requests', [PortalDispatchRequestController::class, 'index']);
        Route::get('/portal/notifications', [PortalNotificationController::class, 'index']);
        Route::post('/portal/notifications/read-all', [PortalNotificationController::class, 'markAllRead']);
        Route::post('/portal/notifications/{notification}/read', [PortalNotificationController::class, 'markRead']);
        Route::get('/portal/dispatch-requests/{dispatchRequest}', [PortalDispatchRequestController::class, 'show']);
        Route::get('/portal/dispatch-requests/{dispatchRequest}/export-pdf', [DispatchRequestController::class, 'exportPdf'])
            ->middleware('throttle:30,1');
        Route::get('/portal/dispatch-requests/{dispatchRequest}/attachments/{attachment}/download', [PortalDispatchRequestController::class, 'downloadAttachment'])
            ->whereNumber('attachment')
            ->middleware('throttle:60,1');

        // Biểu mẫu đã lưu (portal form templates)
        Route::get('/portal/form-templates', [PortalFormTemplateController::class, 'index']);
        Route::get('/portal/form-templates/{portalFormTemplate}', [PortalFormTemplateController::class, 'show']);

        Route::get('/portal/users/for-dispatch-form', UserSearchForDispatchFormController::class);
    });

    Route::middleware([\App\Http\Middleware\LogApiActivity::class, 'throttle:180,1'])->group(function () {
        Route::get('/portal/dispatch-requests/{dispatchRequest}/signed-documents', [PortalDispatchRequestController::class, 'signedDocuments'])
            ->middleware('throttle:60,1');
        Route::post('/portal/dispatch-requests', [PortalDispatchRequestController::class, 'store'])
            ->middleware('idempotency');
        Route::post('/portal/dispatch-request-templates', [DispatchRequestTemplateController::class, 'storePortal'])
            ->middleware(['idempotency', 'throttle:20,1']);
        Route::patch('/portal/dispatch-request-templates/{dispatchRequestTemplate}', [DispatchRequestTemplateController::class, 'updatePortal'])
            ->middleware('throttle:20,1');
        Route::patch('/portal/dispatch-request-templates/{dispatchRequestTemplate}/plan-label', [DispatchRequestTemplateController::class, 'updatePortalPlanLabel'])
            ->middleware('throttle:30,1');
        Route::post('/portal/dispatch-requests/{dispatchRequest}/signed-paper', [PortalDispatchRequestController::class, 'uploadSignedPaper'])
            ->middleware(['idempotency', 'throttle:30,1']);
        Route::patch('/portal/dispatch-requests/{dispatchRequest}/signing-workflow', [PortalDispatchRequestController::class, 'patchSigningWorkflow'])
            ->middleware('throttle:30,1');
        Route::post('/portal/dispatch-requests/{dispatchRequest}/proposal-basis', [PortalDispatchRequestController::class, 'uploadProposalBasis'])
            ->middleware('throttle:30,1');
        Route::patch('/portal/dispatch-requests/{dispatchRequest}/recurring-instance', [PortalDispatchRequestController::class, 'patchRecurringInstance'])
            ->middleware('throttle:60,1');
        Route::post('/portal/dispatch-requests/{dispatchRequest}/submit-recurring', [PortalDispatchRequestController::class, 'submitRecurringInstance'])
            ->middleware('throttle:30,1');

        // Biểu mẫu đã lưu — mutate
        Route::post('/portal/form-templates', [PortalFormTemplateController::class, 'store'])
            ->middleware('throttle:30,1');
        Route::patch('/portal/form-templates/{portalFormTemplate}', [PortalFormTemplateController::class, 'update'])
            ->middleware('throttle:30,1');
        Route::delete('/portal/form-templates/{portalFormTemplate}', [PortalFormTemplateController::class, 'destroy'])
            ->middleware('throttle:30,1');
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
