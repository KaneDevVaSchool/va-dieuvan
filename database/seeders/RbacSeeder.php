<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\PermissionDisplayVi;
use App\Support\PermissionPlainVi;
use App\Support\SuperAdminAccess;
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
            // Transport Program (tp_* redesign)
            'tp_program.view',
            'tp_program.manage',
            'tp_student.view',
            'tp_student.manage',
            'tp_enrollment.manage',
            'tp_attendance.manage',
            'tp_attendance.confirm',
            'tp_driver_assign.manage',
            'tp_cost.manage',
            'tp_import.manage',
            'tp_report.view',
            'tp_audit.view',
            'tp_execution.force_complete',
        ];

        foreach ($permissions as $p) {
            Permission::updateOrCreate(
                ['name' => $p, 'guard_name' => $guard],
                [
                    'display_name' => PermissionDisplayVi::title($p) ?? $p,
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
                'attachment.upload',
                'tp_program.view',
                'tp_program.manage',
                'tp_student.view',
                'tp_student.manage',
                'tp_enrollment.manage',
                'tp_attendance.manage',
                'tp_attendance.confirm',
                'tp_driver_assign.manage',
                'tp_cost.manage',
                'tp_import.manage',
                'tp_report.view',
                'tp_audit.view',
                'tp_execution.force_complete',
            ],
            'department_head' => [
                'request.create',
                'request.update_own',
                'request.cancel_own',
                'request.approve_dept',
                'trip.view_own',
                'attachment.upload',
                'report.view',
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
                'tp_program.view',
                'tp_cost.manage',
                'tp_report.view',
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

        foreach (SuperAdminAccess::emails() as $email) {
            $u = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
            if ($u) {
                SuperAdminAccess::ensureRole($u);
            }
        }
    }
}
