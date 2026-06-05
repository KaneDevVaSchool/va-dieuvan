<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TpAbsenceReasonSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['code' => 'sick', 'label_vi' => 'Ốm / Bệnh', 'default_category' => 'excused', 'sort_order' => 10],
            ['code' => 'family', 'label_vi' => 'Việc gia đình', 'default_category' => 'excused', 'sort_order' => 20],
            ['code' => 'weather', 'label_vi' => 'Thời tiết', 'default_category' => 'excused', 'sort_order' => 30],
            ['code' => 'late_notice', 'label_vi' => 'Báo muộn', 'default_category' => 'excused', 'sort_order' => 40],
            ['code' => 'no_notice', 'label_vi' => 'Không báo trước', 'default_category' => 'unexcused', 'sort_order' => 50],
            ['code' => 'other', 'label_vi' => 'Khác', 'default_category' => 'excused', 'sort_order' => 90],
        ];

        foreach ($rows as $row) {
            DB::table('tp_absence_reasons')->updateOrInsert(
                ['code' => $row['code']],
                array_merge($row, ['active' => true])
            );
        }
    }
}
