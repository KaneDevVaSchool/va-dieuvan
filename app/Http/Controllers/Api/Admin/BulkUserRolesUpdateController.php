<?php

namespace App\Http\Controllers\Api\Admin;

use App\DTOs\Admin\BulkUpdateUserRoleDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\BulkUpdateUserRolesRequest;
use App\Services\Admin\UserRoleService;
use Illuminate\Http\JsonResponse;

class BulkUserRolesUpdateController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:any,system.user_roles.manage');
    }

    public function __invoke(BulkUpdateUserRolesRequest $request, UserRoleService $userRoles): JsonResponse
    {
        $userRoles->bulkUpdateRoles(BulkUpdateUserRoleDTO::fromRequest($request));

        return response()->json([
            'data' => null,
            'message' => 'Roles updated successfully',
            'errors' => null,
        ]);
    }
}
