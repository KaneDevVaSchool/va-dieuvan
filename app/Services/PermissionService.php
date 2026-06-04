<?php

namespace App\Services;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Collection;

class PermissionService
{
    public function list(): Collection
    {
        return Permission::query()->orderBy('name')->get();
    }

    /**
     * Tất cả permissions kèm danh sách role_ids và role_names (tránh N+1).
     */
    public function allWithRoles(): \Illuminate\Support\Collection
    {
        $permissions = Permission::query()->orderBy('name')->get();

        // Load role IDs + names qua pivot một lần duy nhất
        $pivotRows = \DB::table('role_has_permissions as rp')
            ->join('roles', 'roles.id', '=', 'rp.role_id')
            ->select('rp.permission_id', 'roles.id as role_id', 'roles.name as role_name', 'roles.display_name as role_display_name')
            ->get()
            ->groupBy('permission_id');

        return $permissions->map(function (Permission $perm) use ($pivotRows) {
            $rows = $pivotRows->get($perm->id, collect());
            return array_merge($perm->toArray(), [
                'role_ids'   => $rows->pluck('role_id')->values()->all(),
                'role_names' => $rows->map(fn($r) => ['id' => $r->role_id, 'name' => $r->role_name, 'display_name' => $r->role_display_name])->values()->all(),
            ]);
        });
    }

    public function find(int $id): ?Permission
    {
        return Permission::query()->find($id);
    }

    public function create(array $data): Permission
    {
        return Permission::create([
            'name' => $data['name'],
            'guard_name' => $data['guard_name'] ?? 'web',
            'display_name' => $data['display_name'] ?? null,
            'plain_description' => array_key_exists('plain_description', $data) ? $data['plain_description'] : null,
        ]);
    }

    public function update(Permission $permission, array $data): Permission
    {
        $permission->fill([
            'name' => $data['name'] ?? $permission->name,
            'display_name' => array_key_exists('display_name', $data) ? $data['display_name'] : $permission->display_name,
            'plain_description' => array_key_exists('plain_description', $data)
                ? $data['plain_description']
                : $permission->plain_description,
        ]);
        $permission->save();

        return $permission->refresh();
    }

    public function delete(Permission $permission): void
    {
        $permission->delete();
    }
}
