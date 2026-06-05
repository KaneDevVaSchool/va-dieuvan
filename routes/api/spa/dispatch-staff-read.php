<?php

/**
 * Chỉ điều vận: superadmin, admin, dispatcher (dispatch.staff).
 * Chi tiết quyền từng hành động vẫn nằm ở FormRequest / policy.
 */

use App\Http\Controllers\Api\Admin\DispatchSettingController;
use App\Http\Controllers\Api\Admin\FeatureToggleController;
use App\Http\Controllers\Api\Admin\PermissionController;
use App\Http\Controllers\Api\Admin\RoleController;
use App\Http\Controllers\Api\Admin\UserListController;
use App\Http\Controllers\Api\Admin\UserRoleController;
use App\Http\Controllers\Api\Admin\UserSearchController;
use App\Http\Controllers\Api\Audit\AuditLogController;
use App\Http\Controllers\Api\Operational\DriverComplianceDocumentController;
use App\Http\Controllers\Api\Operational\DriverWorkloadController;
use App\Http\Controllers\Api\Operational\VehicleComplianceDocumentController;
use App\Http\Controllers\Api\OperationalResourceController;
use App\Http\Controllers\Api\ReferencePricingController;
use App\Http\Controllers\Api\Reports\ReportController;
use App\Http\Controllers\Api\RequestController;
use App\Http\Controllers\Api\UserSearchForDispatchFormController;
use App\Http\Controllers\Api\UserSearchForDriverAssignmentController;
use Illuminate\Support\Facades\Route;

Route::get('/vehicles', [OperationalResourceController::class, 'vehicles']);
Route::get('/vehicles/{vehicle}', [OperationalResourceController::class, 'showVehicle']);
Route::get('/vehicles/{vehicle}/conflicts', [OperationalResourceController::class, 'vehicleScheduleConflicts']);
Route::get('/vehicles/{vehicle}/compliance-documents', [VehicleComplianceDocumentController::class, 'index']);
Route::get('/vehicles/{vehicle}/compliance-audit', [VehicleComplianceDocumentController::class, 'auditLogs']);
Route::get('/drivers', [OperationalResourceController::class, 'drivers']);
Route::get('/drivers/workload', [DriverWorkloadController::class, 'workload']);
Route::get('/drivers/{driver}/workload-detail', [DriverWorkloadController::class, 'workloadDetail']);
Route::get('/drivers/{driver}', [OperationalResourceController::class, 'showDriver']);
Route::get('/drivers/{driver}/compliance-documents', [DriverComplianceDocumentController::class, 'index']);
Route::get('/drivers/{driver}/compliance-audit', [DriverComplianceDocumentController::class, 'auditLogs']);
Route::get('/transport-providers', [OperationalResourceController::class, 'transportProviders']);
Route::get('/users/for-driver-assignment', UserSearchForDriverAssignmentController::class);
Route::get('/users/for-dispatch-form', UserSearchForDispatchFormController::class);

Route::controller(RequestController::class)->group(function () {
    Route::get('/requests', 'index');
    Route::get('/dept/summary', 'deptSummary')->middleware('permission:request.approve_dept');
});

Route::controller(ReportController::class)->group(function () {
    Route::get('/reports/summary', 'summary');
});

Route::get('/reference-pricing', [ReferencePricingController::class, 'index'])->middleware('throttle:60,1');
Route::get('/reference-pricing/suggest', [ReferencePricingController::class, 'suggest'])->middleware('throttle:60,1');
Route::get('/reference-pricing/revisions', [ReferencePricingController::class, 'revisions'])
    ->middleware(['permission:reference_pricing.manage', 'throttle:60,1']);

Route::controller(AuditLogController::class)->group(function () {
    Route::get('/audit-logs', 'index')->middleware('throttle:60,1');
});

Route::get('/dispatch-form-settings', [DispatchSettingController::class, 'indexForWizard']);

Route::prefix('admin')->group(function () {
    Route::get('users', [UserListController::class, 'index'])->middleware('throttle:60,1');
    Route::get('users/search', UserSearchController::class)->middleware('throttle:60,1');
    Route::get('users/{user}/roles', [UserRoleController::class, 'show']);
    Route::put('users/{user}/roles', [UserRoleController::class, 'update']);

    Route::apiResource('roles', RoleController::class)->except(['create', 'edit']);
    Route::get('permissions/with-roles', [PermissionController::class, 'withRoles'])->middleware('throttle:60,1');
    Route::apiResource('permissions', PermissionController::class)->except(['create', 'edit']);
    Route::apiResource('feature-toggles', FeatureToggleController::class)->except(['create', 'edit']);

    Route::get('dispatch-settings', [DispatchSettingController::class, 'show']);
    Route::put('dispatch-settings', [DispatchSettingController::class, 'update']);
});

// Transport Program redesign (tp_*) — đọc
Route::prefix('tp-programs')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\TransportProgram\TpProgramController::class, 'index']);
    Route::get('/{tpProgram}', [\App\Http\Controllers\Api\TransportProgram\TpProgramController::class, 'show']);
    Route::get('/{tpProgram}/days', [\App\Http\Controllers\Api\TransportProgram\TpProgramDayController::class, 'index']);
    Route::get('/{tpProgram}/enrollments', [\App\Http\Controllers\Api\TransportProgram\TpEnrollmentController::class, 'index']);
    Route::get('/{tpProgram}/audit', [\App\Http\Controllers\Api\TransportProgram\TpAuditController::class, 'index']);
    Route::get('/{tpProgram}/reports/absence', [\App\Http\Controllers\Api\TransportProgram\TpReportAbsenceController::class, 'index']);
    Route::get('/{tpProgram}/reports/cost', [\App\Http\Controllers\Api\TransportProgram\TpReportCostController::class, 'index']);
});

Route::prefix('tp-program-days')->group(function () {
    Route::get('/{tpProgramDay}', [\App\Http\Controllers\Api\TransportProgram\TpProgramDayController::class, 'show']);
    Route::get('/{tpProgramDay}/attendance', [\App\Http\Controllers\Api\TransportProgram\TpAttendanceController::class, 'show']);
    Route::get('/{tpProgramDay}/live', \App\Http\Controllers\Api\TransportProgram\TpDayLiveUpdatesController::class)->middleware('throttle:30,1');
});

Route::get('/tp-students', [\App\Http\Controllers\Api\TpStudent\TpStudentController::class, 'index']);
Route::get('/tp-students/{tpStudent}', [\App\Http\Controllers\Api\TpStudent\TpStudentController::class, 'show']);
Route::get('/tp-students/{tpStudent}/programs', [\App\Http\Controllers\Api\TpStudent\TpStudentProgramHistoryController::class, 'index']);

Route::prefix('tp-imports')->group(function () {
    Route::get('/sample', \App\Http\Controllers\Api\TpStudent\TpImportSampleController::class)->middleware('throttle:30,1');
    Route::get('/{tpImportBatch}', [\App\Http\Controllers\Api\TpStudent\TpImportController::class, 'show']);
    Route::get('/{tpImportBatch}/rows', [\App\Http\Controllers\Api\TpStudent\TpImportRowController::class, 'index']);
    Route::get('/{tpImportBatch}/error-report', [\App\Http\Controllers\Api\TpStudent\TpImportErrorReportController::class, 'download'])->middleware('throttle:30,1');
});
