<?php

namespace App\Http\Controllers\Api\System;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\System\AssignRolesRequest;
use App\Http\Requests\Api\System\ListRoleAssignmentsRequest;
use App\Models\User;
use App\Services\System\RoleAssignmentService;
use Illuminate\Http\JsonResponse;

class RoleAssignmentController extends Controller
{
    use ApiResponses;

    public function index(ListRoleAssignmentsRequest $request, RoleAssignmentService $service): JsonResponse
    {
        $data = $request->validated();

        return $this->ok($service->listUsersWithRoles(
            $data['q'] ?? null,
            (int) ($data['per_page'] ?? 25),
        ));
    }

    public function store(AssignRolesRequest $request, RoleAssignmentService $service): JsonResponse
    {
        $data = $request->validated();
        $user = User::query()->findOrFail($data['user_id']);

        $service->assign(
            $user,
            $data['role_ids'],
            $request->user()?->id,
            $data['effective_from'] ?? null,
            $data['effective_to'] ?? null,
            (bool) ($data['is_temporary'] ?? false),
            $data['note'] ?? null,
        );

        return $this->ok(['user_id' => $user->id]);
    }
}
