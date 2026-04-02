<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RequestController;
use App\Http\Controllers\Api\DispatchRequestController;
use App\Http\Controllers\Api\TripController;
use App\Http\Controllers\Api\TripCostController;
use App\Http\Controllers\Api\TripOpsController;
use App\Http\Controllers\Api\ReconciliationController;
use App\Http\Controllers\Api\CargoController;
use App\Http\Controllers\Api\RouteController;
use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\StudentController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/requests', [RequestController::class, 'index'])->middleware('permission:trip.view_all');

    Route::post('/dispatch-requests', [DispatchRequestController::class, 'store'])
        ->middleware('permission:request.create');
    Route::post('/dispatch-requests/{dispatchRequest}/paper-received', [DispatchRequestController::class, 'markPaperReceived'])
        ->middleware('permission:request.paper.manage');
    Route::post('/dispatch-requests/{dispatchRequest}/decision', [DispatchRequestController::class, 'approve'])
        ->middleware('permission:request.approve');

    Route::post('/trips/{trip}/assign', [TripController::class, 'assign'])
        ->middleware('permission:trip.assign');

    Route::post('/trips/{trip}/status', [TripOpsController::class, 'updateStatus'])
        ->middleware('permission:trip.update_status');
    Route::post('/trips/{trip}/events', [TripOpsController::class, 'addEvent'])
        ->middleware('permission:trip.event.create');

    Route::post('/trips/{trip}/costs', [TripCostController::class, 'store'])
        ->middleware('permission:any,trip.record.create,trip.update_status');
    Route::post('/trip-costs/{tripCost}/decision', [TripCostController::class, 'decide'])
        ->middleware('permission:trip.cost.reconcile');
    Route::patch('/trip-costs/{tripCost}/override', [TripCostController::class, 'override'])
        ->middleware('permission:data.override_confirmed');

    Route::post('/reconciliation-periods', [ReconciliationController::class, 'createPeriod'])
        ->middleware('permission:payment.reconcile');
    Route::post('/reconciliation-periods/{reconciliationPeriod}/lock', [ReconciliationController::class, 'lockPeriod'])
        ->middleware('permission:payment.reconcile');
    Route::post('/reconciliation-periods/{reconciliationPeriod}/generate-payments', [ReconciliationController::class, 'generatePayments'])
        ->middleware('permission:payment.reconcile');
    Route::post('/payments/{payment}/execute', [ReconciliationController::class, 'executePayment'])
        ->middleware('permission:payment.execute');

    Route::post('/cargo-shipments', [CargoController::class, 'store'])
        ->middleware('permission:cargo.manage');
    Route::post('/cargo-shipments/{cargoShipment}/status', [CargoController::class, 'updateStatus'])
        ->middleware('permission:cargo.manage');
    Route::post('/cargo-shipments/{cargoShipment}/pod', [CargoController::class, 'uploadPod'])
        ->middleware('permission:cargo.manage');

    Route::post('/routes', [RouteController::class, 'store'])
        ->middleware('permission:route.manage');
    Route::post('/routes/{route}/versions', [RouteController::class, 'createVersion'])
        ->middleware('permission:route.manage');
    Route::post('/routes/{route}/enroll-students', [RouteController::class, 'enrollStudents'])
        ->middleware('permission:student.manage');
    Route::post('/route-versions/{routeVersion}/decision', [RouteController::class, 'approveVersion'])
        ->middleware('permission:route.manage');
    Route::post('/route-versions/{routeVersion}/generate-trip', [RouteController::class, 'generateTrip'])
        ->middleware('permission:route.manage');

    Route::post('/students', [StudentController::class, 'store'])
        ->middleware('permission:student.manage');

    Route::post('/attachments', [AttachmentController::class, 'upload'])
        ->middleware('permission:attachment.upload');

    Route::get('/reports/summary', [ReportController::class, 'summary'])
        ->middleware('permission:report.view');
});
