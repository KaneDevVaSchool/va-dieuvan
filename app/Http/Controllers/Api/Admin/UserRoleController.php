<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\SyncUserRolesRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class UserRoleController extends Controller
{
    use ApiResponses;

    public function __construct()
    {
        $this->middleware('permission:any,system.user_roles.manage');
    }

    public function show(User $user): JsonResponse
    {
        $user->load(['roles:id,name,display_name,guard_name']);
        $permissions = $user->getAllPermissions()->pluck('name')->unique()->values()->all();

        return $this->ok([
            'user' => $user->only(['id', 'name', 'email', 'employee_code']),
            'roles' => $user->roles,
            'permissions' => $permissions,
        ]);
    }

    public function update(SyncUserRolesRequest $request, User $user): JsonResponse
    {
        $roleIds = $request->validated('role_ids');
        $roles = Role::query()->whereIn('id', $roleIds)->get();
        $user->syncRoles($roles);
        $user->load(['roles:id,name,display_name,guard_name']);
        $permissions = $user->getAllPermissions()->pluck('name')->unique()->values()->all();

        return $this->ok([
            'user' => $user->only(['id', 'name', 'email', 'employee_code']),
            'roles' => $user->roles,
            'permissions' => $permissions,
        ]);
    }
}
