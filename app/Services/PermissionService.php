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
        ]);
    }

    public function update(Permission $permission, array $data): Permission
    {
        $permission->fill([
            'name' => $data['name'] ?? $permission->name,
            'display_name' => array_key_exists('display_name', $data) ? $data['display_name'] : $permission->display_name,
        ]);
        $permission->save();

        return $permission->refresh();
    }

    public function delete(Permission $permission): void
    {
        $permission->delete();
    }
}
