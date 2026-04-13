<?php

use App\Http\Controllers\Api\Attachments\AttachmentController;
use App\Http\Controllers\Api\Audit\AuditLogController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Cargo\CargoController;
use App\Http\Controllers\Api\Costs\TripCostController;
use App\Http\Controllers\Api\D2D\RouteController;
use App\Http\Controllers\Api\D2D\StudentController;
use App\Http\Controllers\Api\NavBadgesController;
use App\Http\Controllers\Api\Notifications\InboxController;
use App\Http\Controllers\Api\OperationalResourceController;
use App\Http\Controllers\Api\Payments\ReconciliationController;
use App\Http\Controllers\Api\ReferencePricingController;
use App\Http\Controllers\Api\Reports\ReportController;
use App\Http\Controllers\Api\RequestController;
use App\Http\Controllers\Api\Requests\DispatchRequestController;
use App\Http\Controllers\Api\Trips\TripController;
use App\Http\Controllers\Api\Trips\TripOpsController;
use App\Http\Controllers\Api\UserProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:20,1');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('throttle:30,1');

    // Lightweight endpoints (no activity logging middleware to reduce latency)
    Route::middleware(['throttle:120,1'])->group(function () {
        Route::get('/user', [UserProfileController::class, 'show']);

        Route::get('/vehicles', [OperationalResourceController::class, 'vehicles']);
        Route::get('/drivers', [OperationalResourceController::class, 'drivers']);

        Route::get('/notifications/inbox', [InboxController::class, 'index']);
        Route::post('/notifications/read-all', [InboxController::class, 'markAllRead']);
        Route::post('/notifications/{notification}/read', [InboxController::class, 'markRead'])
            ->whereUuid('notification');

        Route::controller(RequestController::class)->group(function () {
            Route::get('/requests', 'index');
        });

        Route::get('/dispatch-requests/{dispatchRequest}', [DispatchRequestController::class, 'show']);

        Route::controller(TripController::class)->group(function () {
            Route::get('/trips', 'index');
            Route::get('/trips/{trip}', 'show');
        });

        Route::get('/trip-costs', [TripCostController::class, 'index']);
        Route::get('/trips/{trip}/costs', [TripCostController::class, 'costsForTrip']);

        Route::controller(CargoController::class)->group(function () {
            Route::get('/cargo-shipments', 'index');
            Route::get('/cargo-shipments/{cargoShipment}', 'show');
        });

        Route::controller(ReconciliationController::class)->group(function () {
            Route::get('/reconciliation-periods', 'periodsIndex');
            Route::get('/reconciliation-periods/{reconciliationPeriod}', 'periodShow');
            Route::get('/payments', 'paymentsIndex');
            Route::get('/payments/{payment}', 'paymentShow');
        });

        Route::controller(RouteController::class)->group(function () {
            Route::get('/routes', 'index');
            Route::get('/routes/{route}', 'show');
        });

        Route::get('/students', [StudentController::class, 'index']);

        Route::controller(ReportController::class)->group(function () {
            Route::get('/reports/summary', 'summary')->middleware('throttle:30,1');
        });

        Route::get('/reference-pricing', [ReferencePricingController::class, 'index'])->middleware('throttle:60,1');

        Route::get('/nav/badges', NavBadgesController::class)->middleware('throttle:60,1');

        Route::controller(AuditLogController::class)->group(function () {
            Route::get('/audit-logs', 'index')->middleware('throttle:60,1');
        });
    });

    // Mutating endpoints: add activity logging + tighter throttles to reduce double-submit races.
    Route::middleware([\App\Http\Middleware\LogApiActivity::class, 'throttle:60,1'])->group(function () {
        Route::patch('/user', [UserProfileController::class, 'update']);

        // Requests / approvals
        Route::prefix('dispatch-requests')->controller(DispatchRequestController::class)->group(function () {
            Route::post('/', 'store')
                ->middleware(['throttle:20,1', 'idempotency'])
                ->name('api.dispatch-requests.store');
            Route::post('/{dispatchRequest}/paper-received', 'markPaperReceived')->middleware('throttle:20,1');
            Route::post('/{dispatchRequest}/decision', 'approve')
                ->middleware(['throttle:10,1', 'idempotency'])
                ->name('api.dispatch-requests.decision');
        });

        // Trips
        Route::prefix('trips')->group(function () {
            Route::controller(TripController::class)->group(function () {
                Route::post('/{trip}/assign', 'assign')
                    ->middleware(['throttle:10,1', 'idempotency'])
                    ->name('api.trips.assign');
            });

            Route::controller(TripOpsController::class)->group(function () {
                Route::post('/{trip}/status', 'updateStatus')->middleware('throttle:30,1');
                Route::post('/{trip}/events', 'addEvent')->middleware('throttle:60,1');
            });

            // Costs (nested under trips)
            Route::controller(TripCostController::class)->group(function () {
                Route::post('/{trip}/costs', 'store')
                    ->middleware(['throttle:60,1', 'idempotency'])
                    ->name('api.trips.costs.store');
            });
        });

        // Costs decisions / overrides
        Route::prefix('trip-costs')->controller(TripCostController::class)->group(function () {
            Route::post('/{tripCost}/decision', 'decide')->middleware('throttle:20,1');
            Route::patch('/{tripCost}/override', 'override')->middleware('throttle:10,1');
        });

        // Payments / reconciliation
        Route::prefix('reconciliation-periods')->controller(ReconciliationController::class)->group(function () {
            Route::post('/', 'createPeriod')->middleware('throttle:10,1');
            Route::post('/{reconciliationPeriod}/lock', 'lockPeriod')->middleware('throttle:10,1');
            Route::post('/{reconciliationPeriod}/generate-payments', 'generatePayments')->middleware('throttle:10,1');
        });
        Route::prefix('payments')->controller(ReconciliationController::class)->group(function () {
            Route::post('/{payment}/execute', 'executePayment')
                ->middleware(['throttle:10,1', 'idempotency'])
                ->name('api.payments.execute');
        });

        // Cargo
        Route::prefix('cargo-shipments')->controller(CargoController::class)->group(function () {
            Route::post('/', 'store')->middleware('throttle:20,1');
            Route::post('/{cargoShipment}/status', 'updateStatus')->middleware('throttle:60,1');
            Route::post('/{cargoShipment}/pod', 'uploadPod')->middleware('throttle:20,1');
        });

        // Door-to-door (D2D)
        Route::prefix('routes')->controller(RouteController::class)->group(function () {
            Route::post('/', 'store')->middleware('throttle:10,1');
            Route::post('/{route}/versions', 'createVersion')->middleware('throttle:10,1');
            Route::post('/{route}/enroll-students', 'enrollStudents')->middleware('throttle:10,1');
        });
        Route::prefix('route-versions')->controller(RouteController::class)->group(function () {
            Route::post('/{routeVersion}/decision', 'approveVersion')->middleware('throttle:10,1');
            Route::post('/{routeVersion}/generate-trip', 'generateTrip')->middleware('throttle:10,1');
        });

        // Students
        Route::prefix('students')->controller(StudentController::class)->group(function () {
            Route::post('/', 'store')->middleware('throttle:20,1');
        });

        // Attachments can be heavier; keep separate throttle
        Route::prefix('attachments')->controller(AttachmentController::class)->group(function () {
            Route::post('/', 'upload')->middleware('throttle:30,1');
            Route::post('/{attachment}/ocr', 'runOcr')->middleware('throttle:15,1');
        });
    });
});
