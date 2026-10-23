<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Operational\SearchUsersForDispatchFormRequest;
use App\Models\User;
use App\Services\Hr\UserWorkInfoService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class UserSearchForDispatchFormController extends Controller
{
    use ApiResponses;

    public function __invoke(SearchUsersForDispatchFormRequest $request, UserWorkInfoService $workInfo): JsonResponse
    {
        $q = $request->validated('q');
        $like = '%'.addcslashes($q, '%_\\').'%';

        $users = User::query()
            ->with(['hrEmployee.department', 'hrEmployee.division'])
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

        $payload = $users->map(function (User $u) use ($workInfo) {
            $info = $workInfo->forUser($u);
            $row = [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'employee_code' => $info['code'],
                'phone' => $info['phone'],
                'avatar_url' => $u->avatar_url,
                'department_name' => $info['department_name'],
                'unit_name' => $info['unit_name'],
            ];

            return $row;
        });

        return $this->ok($payload);
    }
}
