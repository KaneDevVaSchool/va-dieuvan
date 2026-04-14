<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Operational\SearchUsersForDispatchFormRequest;
use App\Models\User;
use App\Services\CmsUserInfoService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class UserSearchForDispatchFormController extends Controller
{
    use ApiResponses;

    public function __invoke(SearchUsersForDispatchFormRequest $request, CmsUserInfoService $cms): JsonResponse
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

        $payload = $users->map(function (User $u) use ($cms) {
            $row = [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'employee_code' => $u->employee_code,
                'phone' => $u->phone,
                'avatar_url' => $u->avatar_url,
                'department_name' => null,
                'unit_name' => null,
            ];
            $cmsUserId = $cms->findCmsUserIdByEmail((string) $u->email);
            if ($cmsUserId !== null) {
                $info = $cms->getLatestUserInfoRow($cmsUserId);
                if (is_array($info)) {
                    $row['department_name'] = $info['department_name'] ?? null;
                    $row['unit_name'] = $info['unit_name'] ?? null;
                }
            }

            return $row;
        });

        return $this->ok($payload);
    }
}
