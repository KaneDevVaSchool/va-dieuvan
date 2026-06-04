<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\PermissionPlainVi;
use Illuminate\Database\Seeder;

/**
 * Đồng bộ danh sách permission (theo chuỗi dùng trong FormRequest / Policy) và gán mặc định cho từng role.
 *
 * Chạy sau khi migrate: php artisan db:seed --class=RbacSeeder
 */
class RbacSeeder extends Seeder
{
    private const GUARD = 'web';

    public function run(): void
    {
        $guard = self::GUARD;

        $roles = [
            ['name' => 'superadmin', 'display_name' => 'Super Admin'],
            ['name' => 'admin', 'display_name' => 'Admin'],
            ['name' => 'dispatcher', 'display_name' => 'Dispatcher'],
            ['name' => 'driver', 'display_name' => 'Tài xế'],
            ['name' => 'accountant', 'display_name' => 'Kế toán'],
            ['name' => 'internal_user', 'display_name' => 'User nội bộ'],
            ['name' => 'department_head', 'display_name' => 'Trưởng đơn vị'],
        ];

        foreach ($roles as $r) {
            Role::firstOrCreate(
                ['name' => $r['name'], 'guard_name' => $guard],
                ['display_name' => $r['display_name']]
            );
        }

        // Danh sách đầy đủ: khi thêm API mới, thêm key vào đây (và permission_plain_vi.json nếu cần mô tả).
        $permissions = [
            // Yêu cầu điều xe
            'request.create',
            'request.update_own',
            'request.cancel_own',
            'request.approve',
            'request.fill_price',
            'request.approve_dept',
            'request.paper.manage',
            // Chuyến
            'trip.assign',
            'trip.view_all',
            'trip.view_own',
            'trip.update_status',
            'trip.record.create',
            'trip.event.create',
            // Chi phí chuyến
            'trip.cost.view',
            'trip.cost.reconcile',
            // Thanh toán / báo cáo
            'payment.reconcile',
            'payment.execute',
            'report.view',
            'report.export',
            // Vận hành khác
            'cargo.manage',
            'student.manage',
            'attachment.upload',
            'reference_pricing.manage',
            'resource.driver.manage',
            'resource.vehicle.manage',
            'resource.provider.manage',
            // Người dùng / hệ thống
            'user.manage',
            'audit_log.view',
            'data.override_confirmed',
            'system.roles.manage',
            'system.permissions.manage',
            'system.user_roles.manage',
            'system.feature_toggles.manage',
            'dispatch.settings.manage',
            // P2P policy (học sinh chính sách)
            'policy_trip.view',
            'policy_trip.assign_driver',
            'policy_trip.cancel',
            'student_policy.manage',
            'school_calendar.manage',
        ];

        foreach ($permissions as $p) {
            Permission::updateOrCreate(
                ['name' => $p, 'guard_name' => $guard],
                [
                    'display_name' => $p,
                    'plain_description' => PermissionPlainVi::text($p),
                ]
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
                'trip.cost.view',
                'attachment.upload',
            ],
            'dispatcher' => [
                'request.create',
                'request.update_own',
                'request.cancel_own',
                'request.approve',
                'request.fill_price',
                'request.paper.manage',
                'trip.assign',
                'trip.view_all',
                'trip.view_own',
                'trip.update_status',
                'trip.record.create',
                'trip.event.create',
                'trip.cost.view',
                'trip.cost.reconcile',
                'report.view',
                'report.export',
                'reference_pricing.manage',
                'resource.driver.manage',
                'resource.vehicle.manage',
                'resource.provider.manage',
                'cargo.manage',
                'student.manage',
                'attachment.upload',
                'policy_trip.view',
                'policy_trip.assign_driver',
                'policy_trip.cancel',
                'student_policy.manage',
                'school_calendar.manage',
            ],
            'department_head' => [
                'request.approve_dept',
                'trip.view_own',
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
            'superadmin' => $permissions,
        ];

        foreach ($map as $roleName => $perms) {
            $role = Role::query()->where('name', $roleName)->where('guard_name', $guard)->first();
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
