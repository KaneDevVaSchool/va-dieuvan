<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\ListUsersForRolesRequest;
use App\Models\User;
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

    public function index(ListUsersForRolesRequest $request): JsonResponse
    {
        $data = $request->validated();
        $q = isset($data['q']) ? trim((string) $data['q']) : '';
        $assignment = $data['assignment'] ?? 'all';
        $perPage = $data['per_page'] ?? '10';
        $page = max(1, (int) ($data['page'] ?? 1));

        $roleNames = isset($data['roles']) && is_array($data['roles']) ? array_values(array_filter($data['roles'])) : [];

        $like = $q !== '' ? '%'.addcslashes($q, '%_\\').'%' : null;

        $base = User::query()
            ->select(['id', 'name', 'email', 'employee_code'])
            ->with([
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
                'items' => $items,
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
            'items' => $paginator->items(),
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
}
