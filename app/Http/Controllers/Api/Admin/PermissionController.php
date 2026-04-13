<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\StorePermissionRequest;
use App\Http\Requests\Api\Admin\UpdatePermissionRequest;
use App\Models\Permission;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;

class PermissionController extends Controller
{
    use ApiResponses;

    public function __construct()
    {
        $this->middleware('permission:any,system.permissions.manage');
    }

    public function index(PermissionService $permissions): JsonResponse
    {
        return $this->ok($permissions->list());
    }

    public function show(Permission $permission): JsonResponse
    {
        return $this->ok($permission);
    }

    public function store(StorePermissionRequest $request, PermissionService $permissions): JsonResponse
    {
        return $this->created($permissions->create($request->validated()));
    }

    public function update(UpdatePermissionRequest $request, Permission $permission, PermissionService $permissions): JsonResponse
    {
        return $this->ok($permissions->update($permission, $request->validated()));
    }

    public function destroy(Permission $permission, PermissionService $permissions): JsonResponse
    {
        $permissions->delete($permission);

        return $this->ok(['deleted' => true]);
    }
}
