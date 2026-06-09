<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\ListUsersForRolesRequest;
use App\Models\User;
use App\Services\CmsUserInfoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;

class UserListController extends Controller
{
    use ApiResponses;

    private const MAX_ALL = 500;

    public function __construct()
    {
        $this->middleware('permission:any,system.user_roles.manage');
    }

    public function index(ListUsersForRolesRequest $request, CmsUserInfoService $cms): JsonResponse
    {
        $data = $request->validated();
        $q = isset($data['q']) ? trim((string) $data['q']) : '';
        $assignment = $data['assignment'] ?? 'all';
        $perPage = $data['per_page'] ?? '10';
        $page = max(1, (int) ($data['page'] ?? 1));

        $roleNames = isset($data['roles']) && is_array($data['roles']) ? array_values(array_filter($data['roles'])) : [];

        $like = $q !== '' ? '%'.addcslashes($q, '%_\\').'%' : null;

        $base = User::query()
            ->select(['id', 'name', 'email', 'employee_code', 'avatar_url', 'department_id'])
            ->with([
                'department:id,name,code',
                'roles' => static function ($r) {
                    $r->select('roles.id', 'roles.name', 'roles.display_name', 'roles.guard_name')
                        ->orderBy('roles.name');
                },
            ])
            ->withCount('roles')
            ->when($like !== null, function (Builder $b) use ($like) {
                $b->where(function (Builder $w) use ($like) {
                    $w->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('employee_code', 'like', $like);
                });
            })
            ->when(count($roleNames) > 0, function (Builder $b) use ($roleNames) {
                $b->whereHas('roles', fn (Builder $r) => $r->whereIn('name', $roleNames));
            })
            ->when($assignment === 'assigned', fn (Builder $b) => $b->has('roles'))
            ->when($assignment === 'unassigned', fn (Builder $b) => $b->doesntHave('roles'))
            ->orderBy('name')
            ->orderBy('email');

        if ($perPage === 'all') {
            $total = (clone $base)->count();
            $items = (clone $base)->limit(self::MAX_ALL)->get();

            return $this->ok([
                'items' => $this->formatUserRows($items, $cms),
                'meta' => [
                    'current_page' => 1,
                    'per_page' => $items->count(),
                    'per_page_mode' => 'all',
                    'total' => $total,
                    'last_page' => 1,
                    'truncated' => $total > self::MAX_ALL,
                    'cap' => self::MAX_ALL,
                ],
            ]);
        }

        $pp = (int) $perPage;
        $paginator = $base->paginate($pp, ['*'], 'page', $page);

        return $this->ok([
            'items' => $this->formatUserRows(collect($paginator->items()), $cms),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'per_page_mode' => 'paged',
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'truncated' => false,
                'cap' => null,
            ],
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, User>|iterable<int, User>  $users
     * @return list<array<string, mixed>>
     */
    private function formatUserRows(iterable $users, CmsUserInfoService $cms): array
    {
        $out = [];
        foreach ($users as $user) {
            $out[] = $this->formatUserRow($user, $cms);
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatUserRow(User $user, CmsUserInfoService $cms): array
    {
        $departmentName = $user->department?->name;
        $positionName = null;
        $cmsDepartmentName = null;

        $cmsUserId = $cms->findCmsUserIdByEmail((string) $user->email);
        if ($cmsUserId !== null) {
            $info = $cms->getLatestUserInfoRow($cmsUserId);
            if (is_array($info)) {
                $positionName = isset($info['position_name']) ? trim((string) $info['position_name']) : null;
                if ($positionName === '') {
                    $positionName = null;
                }
                $cmsDepartmentName = isset($info['department_name']) ? trim((string) $info['department_name']) : null;
                if ($cmsDepartmentName === '') {
                    $cmsDepartmentName = null;
                }
            }
        }

        if ($departmentName === null || $departmentName === '') {
            $departmentName = $cmsDepartmentName;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'employee_code' => $user->employee_code,
            'avatar_url' => $user->avatar_url,
            'department_name' => $departmentName,
            'position_name' => $positionName,
            'roles' => $user->roles,
            'roles_count' => $user->roles_count ?? $user->roles?->count() ?? 0,
        ];
    }
}
