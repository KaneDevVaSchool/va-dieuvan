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
use App\Http\Controllers\Api\P2pPolicy\PolicyRouteController;
use App\Http\Controllers\Api\P2pPolicy\PolicyTripAssignDriverController;
use App\Http\Controllers\Api\P2pPolicy\PolicyTripCancelController;
use App\Http\Controllers\Api\P2pPolicy\PolicyTripGenerateController;
use App\Http\Controllers\Api\P2pPolicy\SchoolCalendarController;
use App\Http\Controllers\Api\P2pPolicy\StudentPolicyController;
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
});

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
    Route::post('/', 'store')->middleware('throttle:20,1');
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

Route::post('/policy-routes', [PolicyRouteController::class, 'store'])->middleware('throttle:30,1');

Route::post('/policy-trips/generate', PolicyTripGenerateController::class)
    ->middleware('throttle:10,1');

Route::patch('/policy-trips/{policyTrip}/assign-driver', PolicyTripAssignDriverController::class)
    ->middleware('throttle:60,1');
Route::patch('/policy-trips/{policyTrip}/cancel', PolicyTripCancelController::class)
    ->middleware('throttle:60,1');

Route::prefix('student-policies')->controller(StudentPolicyController::class)->group(function () {
    Route::post('/', 'store')->middleware('throttle:30,1');
    Route::patch('/{studentPolicy}', 'update')->middleware('throttle:30,1');
    Route::delete('/{studentPolicy}', 'destroy')->middleware('throttle:30,1');
});

Route::prefix('school-calendars')->controller(SchoolCalendarController::class)->group(function () {
    Route::post('/generate-month', 'generateMonth')->middleware('throttle:10,1');
    Route::post('/bulk', 'bulkImport')->middleware('throttle:10,1');
    Route::patch('/{date}', 'update')
        ->where('date', '[0-9]{4}-[0-9]{2}-[0-9]{2}')
        ->middleware('throttle:30,1');
});
