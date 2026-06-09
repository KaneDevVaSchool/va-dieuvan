<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Portal\SearchPortalDeptHeadsRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class PortalDeptHeadSearchController extends Controller
{
    use ApiResponses;

    public function __invoke(SearchPortalDeptHeadsRequest $request): JsonResponse
    {
        if (! Role::query()->where('name', 'department_head')->where('guard_name', 'web')->exists()) {
            return $this->ok([]);
        }

        $qTrim = trim((string) ($request->validated('q') ?? ''));

        $users = User::query()
            ->where('is_active', true)
            ->role('department_head')
            ->when($qTrim !== '' && mb_strlen($qTrim) >= 2, function ($query) use ($qTrim): void {
                $like = '%'.addcslashes($qTrim, '%_\\').'%';
                $query->where(function ($w) use ($like): void {
                    $w->where('name', 'like', $like)
                        ->orWhere('employee_code', 'like', $like)
                        ->orWhere('email', 'like', $like);
                });
            })
            ->when($qTrim !== '' && mb_strlen($qTrim) < 2, function ($query): void {
                $query->whereRaw('1 = 0');
            })
            ->orderBy('name')
            ->limit(25)
            ->get(['id', 'name', 'employee_code', 'email']);

        $pickId = (int) ($request->validated('pick') ?? 0);
        if ($pickId > 0) {
            $picked = User::query()
                ->where('is_active', true)
                ->role('department_head')
                ->whereKey($pickId)
                ->first(['id', 'name', 'employee_code', 'email']);
            if ($picked !== null && ! $users->contains(static fn (User $u): bool => (int) $u->id === $pickId)) {
                $users = $users->prepend($picked)->values();
            }
        }

        return $this->ok($users);
    }
}
