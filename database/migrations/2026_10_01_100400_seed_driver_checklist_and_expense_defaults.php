<?php

use App\Models\ExpenseFormSetting;
use App\Models\FeatureToggle;
use App\Models\Permission;
use App\Models\Role;
use App\Support\PermissionDisplayVi;
use App\Support\PermissionPlainVi;
use Database\Seeders\ChecklistTemplateSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Quyền mới gán bổ sung (không sync) để không xóa quyền đã chỉnh tay trên production.
     *
     * @var array<string, list<string>>
     */
    private const ROLE_PERMISSIONS = [
        'driver' => ['checklist.submit', 'expense.submit'],
        'dispatcher' => ['checklist.view', 'checklist.manage', 'expense.view'],
        'accountant' => ['expense.view', 'expense.manage'],
        'admin' => ['checklist.submit', 'checklist.view', 'checklist.manage', 'expense.submit', 'expense.view', 'expense.manage'],
        'superadmin' => ['checklist.submit', 'checklist.view', 'checklist.manage', 'expense.submit', 'expense.view', 'expense.manage'],
    ];

    public function up(): void
    {
        $guard = 'web';

        $all = array_values(array_unique(array_merge(...array_values(self::ROLE_PERMISSIONS))));
        foreach ($all as $name) {
            Permission::query()->updateOrCreate(
                ['name' => $name, 'guard_name' => $guard],
                [
                    'display_name' => PermissionDisplayVi::title($name) ?? $name,
                    'plain_description' => PermissionPlainVi::text($name),
                ],
            );
        }

        foreach (self::ROLE_PERMISSIONS as $roleName => $perms) {
            $role = Role::query()->where('name', $roleName)->where('guard_name', $guard)->first();
            if ($role) {
                $role->givePermissionTo($perms);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ([
            ['key' => 'module.driver_checklist', 'name' => 'Checklist Daily/Weekly tài xế'],
            ['key' => 'module.driver_expense', 'name' => 'Hồ sơ đề xuất / thanh toán chi phí xe'],
        ] as $row) {
            FeatureToggle::query()->firstOrCreate(
                ['key' => $row['key']],
                ['name' => $row['name'], 'module' => 'operations', 'is_enabled' => true],
            );
        }
        Cache::forget(config('feature.cache_key'));

        (new ChecklistTemplateSeeder)->run();

        if (! ExpenseFormSetting::query()->exists()) {
            ExpenseFormSetting::query()->create(ExpenseFormSetting::defaults());
        }
    }

    public function down(): void
    {
        FeatureToggle::query()->whereIn('key', ['module.driver_checklist', 'module.driver_expense'])->delete();
        Permission::query()->whereIn('name', [
            'checklist.submit', 'checklist.view', 'checklist.manage', 'expense.submit', 'expense.view', 'expense.manage',
        ])->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
