<?php

namespace App\Services\System;

use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\User;
use Illuminate\Support\Carbon;

class RoleAssignmentService
{
    /**
     * @return array{items: list<array<string, mixed>>}
     */
    public function listUsersWithRoles(?string $q = null, int $perPage = 25): array
    {
        $query = User::query()->with(['roles:id,name,display_name']);

        if ($q) {
            $query->where(function ($b) use ($q) {
                $b->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        $paginator = $query->orderBy('name')->paginate($perPage);

        $items = collect($paginator->items())->map(fn (User $u) => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'roles' => $u->roles->map(fn (Role $r) => [
                'id' => $r->id,
                'name' => $r->name,
                'display_name' => $r->display_name,
            ])->values()->all(),
        ])->all();

        return [
            'items' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ];
    }

    /**
     * @param  list<int>  $roleIds
     */
    public function assign(
        User $user,
        array $roleIds,
        ?int $assignedBy,
        ?string $effectiveFrom = null,
        ?string $effectiveTo = null,
        bool $temporary = false,
        ?string $note = null,
    ): void {
        $today = Carbon::today()->toDateString();

        foreach ($roleIds as $roleId) {
            RoleAssignment::create([
                'user_id' => $user->id,
                'role_id' => $roleId,
                'effective_from' => $effectiveFrom,
                'effective_to' => $effectiveTo,
                'status' => RoleAssignment::STATUS_ACTIVE,
                'is_temporary' => $temporary,
                'assigned_by' => $assignedBy,
                'note' => $note,
            ]);

            $role = Role::query()->find($roleId);
            if ($role) {
                $user->assignRole($role);
            }
        }
    }
}
