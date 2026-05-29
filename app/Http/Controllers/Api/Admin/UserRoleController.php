<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\SyncUserRolesRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\Admin\UserRoleService;
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
            'user' => $user->only(['id', 'name', 'email', 'employee_code', 'primary_role_name']),
            'roles' => $user->roles,
            'permissions' => $permissions,
        ]);
    }

    public function update(SyncUserRolesRequest $request, User $user, UserRoleService $service): JsonResponse
    {
        $roleIds = $request->validated('role_ids');

        // size:1 is enforced by the FormRequest, so exactly one role_id is present.
        /** @var Role $role */
        $role = Role::query()->findOrFail($roleIds[0]);
        $service->syncPrimaryRole($user, $role);

        $user->load(['roles:id,name,display_name,guard_name']);
        $permissions = $user->getAllPermissions()->pluck('name')->unique()->values()->all();

        return $this->ok([
            'user' => $user->only(['id', 'name', 'email', 'employee_code', 'primary_role_name']),
            'roles' => $user->roles,
            'permissions' => $permissions,
        ]);
    }
}
