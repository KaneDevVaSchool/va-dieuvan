<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\StoreUserRequest;
use App\Services\Admin\UserProvisionService;
use Illuminate\Http\JsonResponse;

/**
 * Trang Phân vai trò: thêm người dùng không có trong CMS.
 */
class UserStoreController extends Controller
{
    use ApiResponses;

    public function __construct()
    {
        $this->middleware('permission:any,system.user_roles.manage');
    }

    public function __invoke(StoreUserRequest $request, UserProvisionService $service): JsonResponse
    {
        $user = $service->create($request->validated(), $request->user()?->id);

        return $this->created([
            'user' => $user->only(['id', 'name', 'email', 'employee_code', 'phone', 'primary_role_name']),
            'roles' => $user->roles,
        ]);
    }
}
