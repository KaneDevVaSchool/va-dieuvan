<?php

/**
 * Ghi: chỉ điều vận (superadmin, admin, dispatcher, department_head).
 */

use App\Http\Controllers\Api\Admin\BulkUserRolesUpdateController;
use App\Http\Controllers\Api\Cargo\CargoController;
use App\Http\Controllers\Api\Costs\TripCostController;
use App\Http\Controllers\Api\Operational\DriverComplianceDocumentController;
use App\Http\Controllers\Api\Operational\VehicleComplianceDocumentController;
use App\Http\Controllers\Api\OperationalResourceController;
use App\Http\Controllers\Api\ReferencePricingController;
use App\Http\Controllers\Api\RequestController;
use App\Http\Controllers\Api\Requests\DispatchRequestController;
use App\Http\Controllers\Api\SignedDocuments\SignedDocumentController;
use App\Http\Controllers\Api\Trips\TripController;
use Illuminate\Support\Facades\Route;

Route::post('v1/users/roles/bulk-update', BulkUserRolesUpdateController::class)
    ->middleware(['permission:any,system.user_roles.manage', 'throttle:60,1']);

Route::patch('/reference-pricing/passenger-fares/{passengerFareRate}', [ReferencePricingController::class, 'updatePassengerFare'])
    ->middleware('permission:reference_pricing.manage');
Route::patch('/reference-pricing/cargo-fares/{cargoFareRate}', [ReferencePricingController::class, 'updateCargoFare'])
    ->middleware('permission:reference_pricing.manage');
Route::patch('/reference-pricing/notes/{pricingNote}', [ReferencePricingController::class, 'updatePricingNote'])
    ->middleware('permission:reference_pricing.manage');

Route::prefix('dispatch-requests')->controller(DispatchRequestController::class)->group(function () {
    Route::post('/{dispatchRequest}/paper-received', 'markPaperReceived')->middleware('throttle:20,1');
    Route::patch('/{dispatchRequest}/fill-price', 'fillPrice')
        ->middleware('throttle:20,1')
        ->name('api.dispatch-requests.fill-price');
    Route::patch('/{dispatchRequest}/pricing-hints', 'applyPricingHints')
        ->middleware(['permission:request.fill_price', 'throttle:20,1'])
        ->name('api.dispatch-requests.apply-pricing-hints');
    Route::post('/{dispatchRequest}/paper-revert', 'revertPaperReceived')->middleware('throttle:20,1');
    Route::post('/{dispatchRequest}/decision', 'approve')
        ->middleware(['throttle:120,1', 'idempotency'])
        ->name('api.dispatch-requests.decision');
    Route::post('/{dispatchRequest}/dept-decision', 'deptDecision')
        ->middleware(['throttle:120,1', 'idempotency', 'permission:request.approve_dept'])
        ->name('api.dispatch-requests.dept-decision');
});

Route::prefix('dispatch-requests')->controller(SignedDocumentController::class)->group(function () {
    Route::post('/{dispatchRequest}/signed-documents', 'store')->middleware('throttle:30,1');
});

Route::prefix('signed-document-versions')->controller(SignedDocumentController::class)->group(function () {
    Route::post('/{signedDocumentVersion}/ocr', 'runOcr')->middleware('throttle:15,1');
    Route::post('/{signedDocumentVersion}/verify', 'verify')->middleware('throttle:30,1');
});

Route::controller(RequestController::class)->group(function () {
    Route::post('/requests/bulk-delete', 'bulkDestroy');
    Route::post('/requests/bulk-restore', 'bulkRestore');
    Route::post('/requests/bulk-force-delete', 'bulkForceDestroy');
    Route::post('/requests/purge-all', 'purgeAll')->middleware('throttle:requests-purge');
});

// Requests — ghi (import)
Route::post('/requests/import', [\App\Http\Controllers\Api\Requests\RequestImportController::class, 'store'])
    ->middleware('throttle:10,1');

// Trips — ghi (import)
Route::post('/trips/import', [\App\Http\Controllers\Api\Trips\TripImportController::class, 'store'])
    ->middleware('throttle:10,1');

// Trip Costs — ghi (import)
Route::post('/trip-costs/import', [\App\Http\Controllers\Api\Costs\TripCostImportController::class, 'store'])
    ->middleware('throttle:10,1');

// Cargo — ghi (import)
Route::post('/cargo-shipments/import', [\App\Http\Controllers\Api\Cargo\CargoImportController::class, 'store'])
    ->middleware('throttle:10,1');

// Resources — ghi (import)
Route::post('/vehicles/import', [\App\Http\Controllers\Api\Resources\VehicleImportController::class, 'store'])
    ->middleware('throttle:10,1');
Route::post('/drivers/import', [\App\Http\Controllers\Api\Resources\DriverImportController::class, 'store'])
    ->middleware('throttle:10,1');

Route::prefix('trips')->group(function () {
    Route::controller(TripController::class)->group(function () {
        Route::post('/{trip}/assign', 'assign')
            ->middleware(['throttle:60,1', 'idempotency'])
            ->name('api.trips.assign');
        Route::post('/{trip}/reschedule', 'reschedule')->middleware('throttle:60,1');
        Route::patch('/{trip}/passenger-list', 'updatePassengerList')->middleware('throttle:30,1');
        Route::patch('/{trip}/passengers/{passenger}/checkin', 'passengerCheckIn')
            ->middleware('throttle:60,1')
            ->where('passenger', '[a-zA-Z0-9_-]+');
        Route::delete('/{trip}/passengers/{passenger}/checkin', 'passengerUncheckIn')
            ->middleware('throttle:60,1')
            ->where('passenger', '[a-zA-Z0-9_-]+');
        Route::patch('/{trip}/passengers/status-bulk', 'passengerBulkSetStatus')
            ->middleware('throttle:60,1');
        Route::patch('/{trip}/passengers/{passenger}/status', 'passengerSetStatus')
            ->middleware('throttle:120,1')
            ->where('passenger', '[a-zA-Z0-9_-]+');
        Route::post('/{trip}/duplicate', 'duplicate')->middleware('throttle:10,1');
    });
});

Route::prefix('trip-costs')->controller(TripCostController::class)->group(function () {
    Route::post('/{tripCost}/decision', 'decide')
        ->middleware(['throttle:120,1', 'idempotency'])
        ->name('api.trip-costs.decision');
    Route::patch('/{tripCost}/override', 'override')->middleware('throttle:10,1');
});

Route::prefix('cargo-shipments')->controller(CargoController::class)->group(function () {
    Route::post('/purge-all', 'purgeAll')->middleware('throttle:cargo-purge');
    Route::post('/bulk-delete', 'bulkDestroy')->middleware('throttle:30,1');
    Route::post('/', 'store')->middleware('throttle:20,1');
    Route::delete('/{cargoShipment}', 'destroy')->middleware('throttle:30,1');
    Route::post('/{cargoShipment}/status', 'updateStatus')->middleware('throttle:60,1');
    Route::post('/{cargoShipment}/pod', 'uploadPod')->middleware('throttle:20,1');
});

Route::post('/drivers', [OperationalResourceController::class, 'storeDriver'])
    ->middleware('throttle:30,1');
Route::post('/drivers/from-user', [OperationalResourceController::class, 'storeDriverFromUser'])
    ->middleware('throttle:30,1');
Route::post('/drivers/bulk-delete', [OperationalResourceController::class, 'bulkDestroyDrivers'])
    ->middleware('throttle:30,1');
Route::post('/drivers/bulk-force-delete', [OperationalResourceController::class, 'bulkForceDeleteDrivers'])
    ->middleware('throttle:30,1');
Route::patch('/drivers/{driver}', [OperationalResourceController::class, 'updateDriver'])
    ->middleware('throttle:30,1');
Route::delete('/drivers/{driver}', [OperationalResourceController::class, 'destroyDriver'])
    ->middleware('throttle:30,1');
Route::post('/drivers/{id}/restore', [OperationalResourceController::class, 'restoreDriver'])
    ->whereNumber('id')
    ->middleware('throttle:30,1');
Route::delete('/drivers/{id}/force', [OperationalResourceController::class, 'forceDeleteDriver'])
    ->whereNumber('id')
    ->middleware('throttle:30,1');
Route::post('/drivers/{driver}/compliance-documents', [DriverComplianceDocumentController::class, 'store'])
    ->middleware('throttle:30,1');
Route::patch('/drivers/{driver}/compliance-documents/{complianceDocument}', [DriverComplianceDocumentController::class, 'update'])
    ->middleware('throttle:30,1');
Route::delete('/drivers/{driver}/compliance-documents/{complianceDocument}', [DriverComplianceDocumentController::class, 'destroy'])
    ->middleware('throttle:30,1');
Route::post('/vehicles', [OperationalResourceController::class, 'storeVehicle'])
    ->middleware('throttle:30,1');
Route::post('/vehicles/bulk-delete', [OperationalResourceController::class, 'bulkDestroyVehicles'])
    ->middleware('throttle:30,1');
Route::post('/vehicles/bulk-force-delete', [OperationalResourceController::class, 'bulkForceDeleteVehicles'])
    ->middleware('throttle:30,1');
Route::patch('/vehicles/{vehicle}', [OperationalResourceController::class, 'updateVehicle'])
    ->middleware('throttle:30,1');
Route::delete('/vehicles/{vehicle}', [OperationalResourceController::class, 'destroyVehicle'])
    ->middleware('throttle:30,1');
Route::post('/vehicles/{id}/restore', [OperationalResourceController::class, 'restoreVehicle'])
    ->whereNumber('id')
    ->middleware('throttle:30,1');
Route::delete('/vehicles/{id}/force', [OperationalResourceController::class, 'forceDeleteVehicle'])
    ->whereNumber('id')
    ->middleware('throttle:30,1');
Route::post('/vehicles/{vehicle}/compliance-documents', [VehicleComplianceDocumentController::class, 'store'])
    ->middleware('throttle:30,1');
Route::patch('/vehicles/{vehicle}/compliance-documents/{complianceDocument}', [VehicleComplianceDocumentController::class, 'update'])
    ->middleware('throttle:30,1');
Route::delete('/vehicles/{vehicle}/compliance-documents/{complianceDocument}', [VehicleComplianceDocumentController::class, 'destroy'])
    ->middleware('throttle:30,1');
Route::post('/transport-providers', [OperationalResourceController::class, 'storeTransportProvider'])
    ->middleware('throttle:30,1');
Route::post('/transport-providers/bulk-delete', [OperationalResourceController::class, 'bulkDestroyTransportProviders'])
    ->middleware('throttle:30,1');
Route::post('/transport-providers/bulk-force-delete', [OperationalResourceController::class, 'bulkForceDeleteTransportProviders'])
    ->middleware('throttle:30,1');
Route::patch('/transport-providers/{transportProvider}', [OperationalResourceController::class, 'updateTransportProvider'])
    ->middleware('throttle:30,1');
Route::delete('/transport-providers/{transportProvider}', [OperationalResourceController::class, 'destroyTransportProvider'])
    ->middleware('throttle:30,1');
Route::post('/transport-providers/{id}/restore', [OperationalResourceController::class, 'restoreTransportProvider'])
    ->whereNumber('id')
    ->middleware('throttle:30,1');
Route::delete('/transport-providers/{id}/force', [OperationalResourceController::class, 'forceDeleteTransportProvider'])
    ->whereNumber('id')
    ->middleware('throttle:30,1');

// Transport Program redesign (tp_*) — ghi
Route::prefix('tp-programs')->group(function () {
    Route::post('/import', [\App\Http\Controllers\Api\TransportProgram\TpProgramImportController::class, 'store'])->middleware('throttle:10,1');
    Route::post('/purge-all', [\App\Http\Controllers\Api\TransportProgram\TpProgramController::class, 'purgeAll'])->middleware('throttle:tp-programs-purge');
    Route::post('/', [\App\Http\Controllers\Api\TransportProgram\TpProgramController::class, 'store'])->middleware('throttle:30,1');
    Route::post('/bulk-delete', [\App\Http\Controllers\Api\TransportProgram\TpProgramController::class, 'bulkDestroy'])->middleware('throttle:20,1');
    Route::patch('/{tpProgram}', [\App\Http\Controllers\Api\TransportProgram\TpProgramController::class, 'update'])->middleware('throttle:30,1');
    Route::delete('/{tpProgram}', [\App\Http\Controllers\Api\TransportProgram\TpProgramController::class, 'destroy'])->middleware('throttle:20,1');

    Route::post('/{tpProgram}/activate', [\App\Http\Controllers\Api\TransportProgram\TpProgramLifecycleController::class, 'activate'])->middleware('throttle:30,1');
    Route::post('/{tpProgram}/pause', [\App\Http\Controllers\Api\TransportProgram\TpProgramLifecycleController::class, 'pause'])->middleware('throttle:30,1');
    Route::post('/{tpProgram}/cancel', [\App\Http\Controllers\Api\TransportProgram\TpProgramLifecycleController::class, 'cancel'])->middleware('throttle:30,1');

    Route::post('/{tpProgram}/enrollments', [\App\Http\Controllers\Api\TransportProgram\TpEnrollmentController::class, 'store'])->middleware('throttle:30,1');
    Route::delete('/{tpProgram}/enrollments/{student}', [\App\Http\Controllers\Api\TransportProgram\TpEnrollmentController::class, 'destroy'])->middleware('throttle:30,1');
});

Route::prefix('tp-program-days')->group(function () {
    Route::patch('/{tpProgramDay}', [\App\Http\Controllers\Api\TransportProgram\TpProgramDayController::class, 'update'])->middleware('throttle:60,1');
    Route::patch('/{tpProgramDay}/assign-driver', [\App\Http\Controllers\Api\TransportProgram\TpProgramDayDriverController::class, 'assign'])->middleware('throttle:60,1');
    Route::delete('/{tpProgramDay}/driver', [\App\Http\Controllers\Api\TransportProgram\TpProgramDayDriverController::class, 'remove'])->middleware('throttle:60,1');
    Route::patch('/{tpProgramDay}/backup-driver', [\App\Http\Controllers\Api\TransportProgram\TpProgramDayDriverController::class, 'assignBackup'])->middleware('throttle:60,1');
    Route::delete('/{tpProgramDay}/backup-driver', [\App\Http\Controllers\Api\TransportProgram\TpProgramDayDriverController::class, 'removeBackup'])->middleware('throttle:60,1');
    Route::post('/{tpProgramDay}/absences', [\App\Http\Controllers\Api\TransportProgram\TpDayAbsenceController::class, 'store'])
        ->middleware(['throttle:60,1', 'idempotency']);
    Route::delete('/{tpProgramDay}/absences/{student}', [\App\Http\Controllers\Api\TransportProgram\TpDayAbsenceController::class, 'destroy'])->middleware('throttle:60,1');
    Route::post('/{tpProgramDay}/present', [\App\Http\Controllers\Api\TransportProgram\TpDayPresentController::class, 'store'])
        ->middleware(['throttle:60,1', 'idempotency']);
    Route::post('/{tpProgramDay}/attendance/draft', [\App\Http\Controllers\Api\TransportProgram\TpAttendanceSessionController::class, 'saveDraft'])
        ->middleware(['throttle:60,1', 'idempotency']);
    Route::post('/{tpProgramDay}/attendance/confirm', [\App\Http\Controllers\Api\TransportProgram\TpAttendanceSessionController::class, 'confirm'])
        ->middleware(['throttle:60,1', 'idempotency'])
        ->name('api.tp-program-days.attendance.confirm');
    Route::post('/{tpProgramDay}/attendance/reopen', [\App\Http\Controllers\Api\TransportProgram\TpAttendanceSessionController::class, 'reopen'])
        ->middleware(['throttle:30,1', 'idempotency']);
    Route::post('/{tpProgramDay}/notify-parents/preview', [\App\Http\Controllers\Api\TransportProgram\TpDayNotifyParentsController::class, 'preview'])
        ->middleware('throttle:60,1');
    Route::post('/{tpProgramDay}/notify-parents', [\App\Http\Controllers\Api\TransportProgram\TpDayNotifyParentsController::class, 'store'])
        ->middleware(['throttle:30,1', 'idempotency']);
});

Route::patch('/tp-executions/{tpTripExecution}/cost', [\App\Http\Controllers\Api\TransportProgram\TpExecutionCostController::class, 'update'])->middleware('throttle:30,1');
Route::post('/tp-executions/{tpTripExecution}/force-complete', [\App\Http\Controllers\Api\Driver\DriverTripCompleteController::class, 'forceComplete'])->middleware('throttle:30,1');

// Students (tp_*) — ghi
Route::prefix('tp-students')->group(function () {
    Route::post('/purge-all', [\App\Http\Controllers\Api\TpStudent\TpStudentController::class, 'purgeAll'])->middleware('throttle:tp-students-purge');
    Route::post('/', [\App\Http\Controllers\Api\TpStudent\TpStudentController::class, 'store'])->middleware('throttle:30,1');
    Route::post('/bulk-delete', [\App\Http\Controllers\Api\TpStudent\TpStudentController::class, 'bulkDestroy'])->middleware('throttle:20,1');
    Route::patch('/{tpStudent}', [\App\Http\Controllers\Api\TpStudent\TpStudentController::class, 'update'])->middleware('throttle:30,1');
    Route::delete('/{tpStudent}', [\App\Http\Controllers\Api\TpStudent\TpStudentController::class, 'destroy'])->middleware('throttle:20,1');
});

// Import pipeline (tp_*) — ghi
Route::prefix('tp-imports')->group(function () {
    Route::post('/', [\App\Http\Controllers\Api\TpStudent\TpImportController::class, 'store'])->middleware('throttle:20,1');
    Route::patch('/{tpImportBatch}/mapping', [\App\Http\Controllers\Api\TpStudent\TpImportMappingController::class, 'update'])->middleware('throttle:30,1');
    Route::patch('/{tpImportBatch}/rows/{tpImportRow}', \App\Http\Controllers\Api\TpStudent\TpImportRowUpdateController::class)->middleware('throttle:60,1');
    Route::post('/{tpImportBatch}/apply-fixes', [\App\Http\Controllers\Api\TpStudent\TpImportFixController::class, 'apply'])->middleware('throttle:20,1');
    Route::post('/{tpImportBatch}/execute', [\App\Http\Controllers\Api\TpStudent\TpImportExecuteController::class, 'execute'])->middleware('throttle:10,1');
});
