<?php

/**
 * System Admin — đọc (dispatch.staff).
 */

use App\Http\Controllers\Api\System\SystemAuditController;
use Illuminate\Support\Facades\Route;

Route::get('/audit-logs/{auditLog}/detail', [SystemAuditController::class, 'show'])
    ->middleware('throttle:60,1');
