<?php

namespace App\Services\Admin;

use App\DTOs\Admin\BulkUpdateUserRoleDTO;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class UserRoleService
{
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
                if ($dto->action === 'assign') {
                    $user->assignRole(...$roleList);
                } else {
                    $user->removeRole(...$roleList);
                }
            }
        });
    }
}
