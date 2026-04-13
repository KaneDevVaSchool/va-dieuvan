<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Operational\SearchUsersForDriverAssignmentRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class UserSearchForDriverAssignmentController extends Controller
{
    use ApiResponses;

    public function __invoke(SearchUsersForDriverAssignmentRequest $request): JsonResponse
    {
        $q = $request->validated('q');
        $like = '%'.addcslashes($q, '%_\\').'%';

        $users = User::query()
            ->where('is_active', true)
            ->where(function (Builder $w) use ($like) {
                $w->where('email', 'like', $like)
                    ->orWhere('name', 'like', $like)
                    ->orWhere('employee_code', 'like', $like)
                    ->orWhere('phone', 'like', $like);
            })
            ->orderBy('name')
            ->limit(25)
            ->get(['id', 'name', 'email', 'employee_code', 'phone', 'avatar_url']);

        return $this->ok($users);
    }
}
