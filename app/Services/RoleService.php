<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class RoleService
{
    public function listWithPermissionCount(): Collection
    {
        $labels = config('role_categories.labels', []);
        $default = config('role_categories.default_label', 'Other');

        return Role::query()
            ->withCount('permissions')
            ->orderBy('name')
            ->get()
            ->each(static function (Role $role) use ($labels, $default) {
                $role->setAttribute('category', $labels[$role->name] ?? $default);
            });
    }

    public function find(int $id): ?Role
    {
        return Role::query()->with('permissions:id,name,display_name,guard_name')->find($id);
    }

    public function create(array $data): Role
    {
        return DB::transaction(function () use ($data) {
            $role = Role::create([
                'name' => $data['name'],
                'guard_name' => $data['guard_name'] ?? 'web',
                'display_name' => $data['display_name'] ?? null,
            ]);

            if (! empty($data['permission_ids']) && is_array($data['permission_ids'])) {
                $this->syncPermissionsByIds($role, $data['permission_ids']);
            }

            return $role->load('permissions');
        });
    }

    public function update(Role $role, array $data): Role
    {
        return DB::transaction(function () use ($role, $data) {
            $role->fill([
                'name' => $data['name'] ?? $role->name,
                'display_name' => array_key_exists('display_name', $data) ? $data['display_name'] : $role->display_name,
            ]);
            $role->save();

            if (array_key_exists('permission_ids', $data) && is_array($data['permission_ids'])) {
                $this->syncPermissionsByIds($role, $data['permission_ids']);
            }

            return $role->load('permissions');
        });
    }

    public function delete(Role $role): void
    {
        $roleName = config('permission.superadmin_role', 'superadmin');
        if ($role->name === $roleName) {
            abort(422, 'Không xóa được vai trò Super Admin.');
        }

        $role->delete();
    }

    /**
     * @param  array<int>  $permissionIds
     */
    public function syncPermissionsByIds(Role $role, array $permissionIds): void
    {
        $ids = Permission::query()->whereIn('id', $permissionIds)->pluck('id')->all();
        $role->syncPermissions(
            Permission::query()->whereIn('id', $ids)->get()
        );
    }
}
