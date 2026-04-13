<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\StoreRoleRequest;
use App\Http\Requests\Api\Admin\UpdateRoleRequest;
use App\Models\Role;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;

class RoleController extends Controller
{
    use ApiResponses;

    public function __construct()
    {
        $this->middleware('permission:any,system.roles.manage');
    }

    public function index(RoleService $roles): JsonResponse
    {
        return $this->ok($roles->listWithPermissionCount());
    }

    public function show(Role $role, RoleService $roles): JsonResponse
    {
        $r = $roles->find($role->id);
        abort_if(! $r, 404);

        return $this->ok($r);
    }

    public function store(StoreRoleRequest $request, RoleService $roles): JsonResponse
    {
        $role = $roles->create($request->validated());

        return $this->created($role);
    }

    public function update(UpdateRoleRequest $request, Role $role, RoleService $roles): JsonResponse
    {
        return $this->ok($roles->update($role, $request->validated()));
    }

    public function destroy(Role $role, RoleService $roles): JsonResponse
    {
        $roles->delete($role);

        return $this->ok(['deleted' => true]);
    }
}
