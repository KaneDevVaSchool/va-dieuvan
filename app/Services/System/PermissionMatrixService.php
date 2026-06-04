<?php

namespace App\Services\System;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class PermissionMatrixService
{
    /**
     * @return array{roles: array, modules: array, matrix: array<string, list<int>>}
     */
    public function matrix(): array
    {
        $roles = Role::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'display_name', 'color', 'status']);

        $permissions = Permission::query()
            ->orderBy('module')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $byModule = [];
        foreach ($permissions as $perm) {
            $module = $perm->module ?? 'general';
            if (! isset($byModule[$module])) {
                $byModule[$module] = [
                    'module' => $module,
                    'label' => $module,
                    'permissions' => [],
                ];
            }
            $byModule[$module]['permissions'][] = [
                'id' => $perm->id,
                'name' => $perm->name,
                'label' => $perm->display_name ?? $perm->name,
                'is_sensitive' => (bool) ($perm->is_sensitive ?? false),
                'action_type' => $perm->action_type,
            ];
        }

        $pivotTable = config('permission.table_names.role_has_permissions');
        $rolePermKey = config('permission.column_names.role_pivot_key') ?? 'role_id';
        $permKey = config('permission.column_names.permission_pivot_key') ?? 'permission_id';

        $matrix = [];
        $rows = DB::table($pivotTable)->get([$rolePermKey, $permKey]);
        foreach ($rows as $row) {
            $rid = (string) $row->{$rolePermKey};
            $matrix[$rid] ??= [];
            $matrix[$rid][] = (int) $row->{$permKey};
        }

        return [
            'roles' => $roles->map(fn (Role $r) => [
                'id' => $r->id,
                'name' => $r->name,
                'display_name' => $r->display_name,
                'color' => $r->color,
                'status' => $r->status ?? 'active',
            ])->values()->all(),
            'modules' => array_values($byModule),
            'matrix' => $matrix,
        ];
    }

    /**
     * @param  list<array{role_id:int, grant?:list<int>, revoke?:list<int>}>  $changes
     */
    public function sync(array $changes): int
    {
        $applied = 0;
        foreach ($changes as $change) {
            $role = Role::query()->findOrFail($change['role_id']);
            $grant = $change['grant'] ?? [];
            $revoke = $change['revoke'] ?? [];
            if ($grant) {
                $role->givePermissionTo(Permission::query()->whereIn('id', $grant)->pluck('name'));
                $applied += count($grant);
            }
            if ($revoke) {
                $role->revokePermissionTo(Permission::query()->whereIn('id', $revoke)->pluck('name'));
                $applied += count($revoke);
            }
        }

        return $applied;
    }
}
