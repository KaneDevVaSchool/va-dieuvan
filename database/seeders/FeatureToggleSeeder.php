<?php

namespace Database\Seeders;

use App\Models\FeatureToggle;
use App\Services\FeatureToggleService;
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
            ['key' => 'module.system.roles', 'name' => 'Quyền hạn — Quản lý Role', 'module' => 'system', 'is_enabled' => true],
            ['key' => 'module.system.permissions', 'name' => 'Quyền hạn — Quản lý Permission', 'module' => 'system', 'is_enabled' => true],
            ['key' => 'module.system.user_roles', 'name' => 'Quyền hạn — Gán quyền người dùng', 'module' => 'system', 'is_enabled' => true],
            ['key' => 'module.system.feature_toggles', 'name' => 'Quyền hạn — Feature toggle', 'module' => 'system', 'is_enabled' => true],
            ['key' => 'module.system.audit', 'name' => 'Quyền hạn — Activity log', 'module' => 'system', 'is_enabled' => true],
        ];

        foreach ($rows as $row) {
            FeatureToggle::updateOrCreate(
                ['key' => $row['key']],
                [
                    'name' => $row['name'],
                    'module' => $row['module'],
                    'is_enabled' => $row['is_enabled'],
                ]
            );
        }

        // Seed không đi qua FeatureToggleService; vẫn xóa cache runtime (24h) để menu/flag khớp DB ngay.
        app(FeatureToggleService::class)->clearCache();
    }
}
