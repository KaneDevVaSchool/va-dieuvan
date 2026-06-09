<?php

namespace App\Services\Admin;

use App\DTOs\Admin\BulkUpdateUserRoleDTO;
use App\Models\Role;
use App\Models\User;
use App\Services\Auditing\AuditLogger;
use App\Support\SuperAdminAccess;
use Illuminate\Support\Facades\DB;

final class UserRoleService
{
    /**
     * Assign exactly one primary role to a user, replacing any existing roles.
     * Enforces the single-role-per-user constraint at the service layer.
     */
    public function syncPrimaryRole(User $user, Role $role, ?int $actorId = null): void
    {
        if (SuperAdminAccess::matches($user) && $role->name !== config('permission.superadmin_role', 'superadmin')) {
            abort(422, 'Không thể đổi vai trò tài khoản Super Admin bootstrap.');
        }

        DB::transaction(function () use ($user, $role, $actorId) {
            $previousRoles = $user->roles->pluck('name')->all();

            $user->syncRoles([$role]);
            $user->update([
                'primary_role_name' => $role->name,
                'primary_role_id'   => $role->id,
            ]);

            app(AuditLogger::class)->log(
                actorId: $actorId ?? auth()->id(),
                event: 'user.role_assigned',
                auditable: $user,
                before: ['roles' => $previousRoles],
                after: ['role' => $role->name],
            );
        });
    }

    public function bulkUpdateRoles(BulkUpdateUserRoleDTO $dto): void
    {
        DB::transaction(function () use ($dto) {
            $guard = 'web';

            $uniqueUserIds = array_values(array_unique($dto->userIds));
            $uniqueRoleNames = array_values(array_unique($dto->roles));

            $roleModels = Role::query()
                ->where('guard_name', $guard)
                ->whereIn('name', $uniqueRoleNames)
                ->get();

            if ($roleModels->count() !== count($uniqueRoleNames)) {
                abort(422, 'One or more roles do not exist.');
            }

            $users = User::query()
                ->whereIn('id', $uniqueUserIds)
                ->get();

            if ($users->count() !== count($uniqueUserIds)) {
                abort(422, 'One or more users do not exist.');
            }

            $roleList = $roleModels->all();

            foreach ($users as $user) {
                if (SuperAdminAccess::matches($user)) {
                    $superRole = config('permission.superadmin_role', 'superadmin');
                    if ($dto->action === 'assign') {
                        if (! in_array($superRole, $uniqueRoleNames, true) || count($uniqueRoleNames) !== 1) {
                            abort(422, 'Không thể đổi vai trò tài khoản Super Admin bootstrap.');
                        }
                    } elseif (in_array($superRole, $uniqueRoleNames, true)) {
                        abort(422, 'Không thể gỡ vai trò Super Admin khỏi tài khoản bootstrap.');
                    }
                }

                if ($dto->action === 'assign') {
                    // Enforce single-role: replace all existing roles with the assigned one.
                    // If multiple roles are provided, only the last is kept (bulk UI prevents this).
                    $user->syncRoles($roleList);
                    if (count($roleList) === 1) {
                        $user->update([
                            'primary_role_name' => $roleList[0]->name,
                            'primary_role_id'   => $roleList[0]->id,
                        ]);
                    }
                } else {
                    $user->removeRole(...$roleList);
                    // Clear primary if it was the removed role
                    if (in_array($user->primary_role_name, $uniqueRoleNames, true)) {
                        $user->update(['primary_role_name' => null, 'primary_role_id' => null]);
                    }
                }
            }
        });
    }
}
