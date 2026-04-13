<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        $guard = 'web';

        $roles = [
            ['name' => 'superadmin', 'display_name' => 'Super Admin'],
            ['name' => 'admin', 'display_name' => 'Admin'],
            ['name' => 'dispatcher', 'display_name' => 'Dispatcher'],
            ['name' => 'driver', 'display_name' => 'Tài xế'],
            ['name' => 'accountant', 'display_name' => 'Kế toán'],
            ['name' => 'internal_user', 'display_name' => 'User nội bộ'],
        ];

        foreach ($roles as $r) {
            Role::firstOrCreate(
                ['name' => $r['name'], 'guard_name' => $guard],
                ['display_name' => $r['display_name']]
            );
        }

        $permissions = [
            'request.create',
            'request.update_own',
            'request.cancel_own',
            'request.approve',
            'request.paper.manage',
            'trip.assign',
            'trip.view_all',
            'trip.view_own',
            'trip.update_status',
            'trip.record.create',
            'trip.event.create',
            'trip.cost.view',
            'trip.cost.reconcile',
            'payment.reconcile',
            'payment.execute',
            'cargo.manage',
            'route.manage',
            'student.manage',
            'attachment.upload',
            'report.view',
            'report.export',
            'resource.driver.manage',
            'resource.vehicle.manage',
            'resource.provider.manage',
            'user.manage',
            'audit_log.view',
            'data.override_confirmed',
            'system.roles.manage',
            'system.permissions.manage',
            'system.user_roles.manage',
            'system.feature_toggles.manage',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(
                ['name' => $p, 'guard_name' => $guard],
                ['display_name' => $p]
            );
        }

        $map = [
            'internal_user' => [
                'request.create',
                'request.update_own',
                'request.cancel_own',
                'trip.view_own',
            ],
            'driver' => [
                'trip.view_own',
                'trip.update_status',
                'trip.record.create',
                'trip.event.create',
                'attachment.upload',
            ],
            'dispatcher' => [
                'request.create',
                'request.update_own',
                'request.cancel_own',
                'request.approve',
                'request.paper.manage',
                'trip.assign',
                'trip.view_all',
                'trip.view_own',
                'trip.update_status',
                'trip.record.create',
                'trip.event.create',
                'trip.cost.view',
                'report.view',
                'report.export',
                'resource.driver.manage',
                'resource.vehicle.manage',
                'resource.provider.manage',
                'cargo.manage',
                'route.manage',
                'student.manage',
                'attachment.upload',
            ],
            'accountant' => [
                'trip.view_all',
                'trip.cost.view',
                'trip.cost.reconcile',
                'payment.reconcile',
                'payment.execute',
                'report.view',
                'report.export',
                'resource.provider.manage',
                'attachment.upload',
            ],
            'admin' => $permissions,
        ];

        foreach ($map as $roleName => $perms) {
            $role = Role::where('name', $roleName)->where('guard_name', $guard)->first();
            if (! $role) {
                continue;
            }
            $role->syncPermissions(
                Permission::query()->whereIn('name', $perms)->where('guard_name', $guard)->get()
            );
        }

        $superEmail = config('permission.superadmin_email');
        if ($superEmail) {
            $u = User::query()->where('email', $superEmail)->first();
            if ($u) {
                $u->assignRole('superadmin');
            }
        }
    }
}
