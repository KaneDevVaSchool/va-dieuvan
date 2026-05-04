<?php

/**
 * Ghi: tất cả user SPA (tài xế + điều vận) — cập nhật hồ sơ.
 */

use App\Http\Controllers\Api\UserProfileController;
use Illuminate\Support\Facades\Route;

Route::patch('/user', [UserProfileController::class, 'update']);
