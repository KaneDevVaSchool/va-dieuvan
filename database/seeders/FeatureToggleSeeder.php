<?php

namespace Database\Seeders;

use App\Models\FeatureToggle;
use Illuminate\Database\Seeder;

class FeatureToggleSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['key' => 'module.overview', 'name' => 'Tổng quan & lịch', 'module' => 'overview', 'is_enabled' => true],
            ['key' => 'module.operations', 'name' => 'Điều vận (yêu cầu, chuyến, D2D…)', 'module' => 'operations', 'is_enabled' => true],
            ['key' => 'module.finance', 'name' => 'Kế toán / đối soát', 'module' => 'finance', 'is_enabled' => true],
            ['key' => 'module.reports', 'name' => 'Báo cáo & giá', 'module' => 'reports', 'is_enabled' => true],
            ['key' => 'module.help', 'name' => 'Hỗ trợ & thông báo', 'module' => 'help', 'is_enabled' => true],
            ['key' => 'module.system.roles', 'name' => 'Hệ thống — Quản lý Role', 'module' => 'system', 'is_enabled' => true],
            ['key' => 'module.system.permissions', 'name' => 'Hệ thống — Quản lý Permission', 'module' => 'system', 'is_enabled' => true],
            ['key' => 'module.system.user_roles', 'name' => 'Hệ thống — Gán quyền người dùng', 'module' => 'system', 'is_enabled' => true],
            ['key' => 'module.system.feature_toggles', 'name' => 'Hệ thống — Feature toggle', 'module' => 'system', 'is_enabled' => true],
            ['key' => 'module.system.audit', 'name' => 'Hệ thống — Activity log', 'module' => 'system', 'is_enabled' => true],
        ];

        foreach ($rows as $row) {
            FeatureToggle::firstOrCreate(
                ['key' => $row['key']],
                [
                    'name' => $row['name'],
                    'module' => $row['module'],
                    'is_enabled' => $row['is_enabled'],
                ]
            );
        }
    }
}
