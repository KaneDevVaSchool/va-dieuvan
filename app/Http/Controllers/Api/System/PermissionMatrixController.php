<?php

namespace App\Http\Controllers\Api\System;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\System\PermissionMatrixRequest;
use App\Http\Requests\Api\System\SyncPermissionMatrixRequest;
use App\Services\System\PermissionMatrixService;
use Illuminate\Http\JsonResponse;

class PermissionMatrixController extends Controller
{
    use ApiResponses;

    public function show(PermissionMatrixRequest $request, PermissionMatrixService $matrix): JsonResponse
    {
        return $this->ok($matrix->matrix());
    }

    public function sync(SyncPermissionMatrixRequest $request, PermissionMatrixService $matrix): JsonResponse
    {
        $applied = $matrix->sync($request->validated('changes'));

        return $this->ok(['applied' => $applied]);
    }
}
