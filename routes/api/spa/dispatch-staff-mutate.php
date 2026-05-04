<?php

/**
 * Ghi: chỉ điều vận (superadmin, admin, dispatcher).
 */

use App\Http\Controllers\Api\Cargo\CargoController;
use App\Http\Controllers\Api\Costs\TripCostController;
use App\Http\Controllers\Api\D2D\RouteController;
use App\Http\Controllers\Api\Operational\DriverComplianceDocumentController;
use App\Http\Controllers\Api\Operational\VehicleComplianceDocumentController;
use App\Http\Controllers\Api\OperationalResourceController;
use App\Http\Controllers\Api\Payments\ReconciliationController;
use App\Http\Controllers\Api\ReferencePricingController;
use App\Http\Controllers\Api\RequestController;
use App\Http\Controllers\Api\Requests\DispatchRequestController;
use App\Http\Controllers\Api\Admin\BulkUserRolesUpdateController;
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
    Route::post('/', 'store')
        ->middleware(['throttle:20,1', 'idempotency'])
        ->name('api.dispatch-requests.store');
    Route::post('/{dispatchRequest}/paper-received', 'markPaperReceived')->middleware('throttle:20,1');
    Route::post('/{dispatchRequest}/paper-revert', 'revertPaperReceived')->middleware('throttle:20,1');
    Route::post('/{dispatchRequest}/decision', 'approve')
        ->middleware(['throttle:120,1', 'idempotency'])
        ->name('api.dispatch-requests.decision');
});

Route::controller(RequestController::class)->group(function () {
    Route::post('/requests/bulk-delete', 'bulkDestroy');
    Route::post('/requests/bulk-restore', 'bulkRestore');
    Route::post('/requests/bulk-force-delete', 'bulkForceDestroy');
});

Route::prefix('trips')->group(function () {
    Route::controller(TripController::class)->group(function () {
        Route::post('/{trip}/assign', 'assign')
            ->middleware(['throttle:60,1', 'idempotency'])
            ->name('api.trips.assign');
        Route::post('/{trip}/reschedule', 'reschedule')->middleware('throttle:60,1');
        Route::patch('/{trip}/passenger-list', 'updatePassengerList')->middleware('throttle:30,1');
    });
});

Route::prefix('trip-costs')->controller(TripCostController::class)->group(function () {
    Route::post('/{tripCost}/decision', 'decide')->middleware('throttle:20,1');
    Route::patch('/{tripCost}/override', 'override')->middleware('throttle:10,1');
});

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

Route::prefix('cargo-shipments')->controller(CargoController::class)->group(function () {
    Route::post('/', 'store')->middleware('throttle:20,1');
    Route::post('/{cargoShipment}/status', 'updateStatus')->middleware('throttle:60,1');
    Route::post('/{cargoShipment}/pod', 'uploadPod')->middleware('throttle:20,1');
});

Route::prefix('routes')->controller(RouteController::class)->group(function () {
    Route::post('/', 'store')->middleware('throttle:10,1');
    Route::post('/{route}/versions', 'createVersion')->middleware('throttle:10,1');
    Route::post('/{route}/enroll-students', 'enrollStudents')->middleware('throttle:10,1');
});
Route::prefix('route-versions')->controller(RouteController::class)->group(function () {
    Route::post('/{routeVersion}/decision', 'approveVersion')->middleware('throttle:10,1');
    Route::post('/{routeVersion}/generate-trip', 'generateTrip')->middleware('throttle:10,1');
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
