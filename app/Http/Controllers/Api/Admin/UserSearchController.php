<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\SearchUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class UserSearchController extends Controller
{
    use ApiResponses;

    public function __construct()
    {
        $this->middleware('permission:any,system.user_roles.manage');
    }

    public function __invoke(SearchUserRequest $request): JsonResponse
    {
        $q = $request->validated('q');
        $like = '%'.addcslashes($q, '%_\\').'%';

        $users = User::query()
            ->where(function ($query) use ($like) {
                $query->where('email', 'like', $like)
                    ->orWhere('name', 'like', $like)
                    ->orWhere('employee_code', 'like', $like);
            })
            ->orderBy('name')
            ->limit(25)
            ->get(['id', 'name', 'email', 'employee_code']);

        return $this->ok($users);
    }
}
