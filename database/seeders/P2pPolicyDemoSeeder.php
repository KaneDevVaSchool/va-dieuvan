<?php

namespace Database\Seeders;

use App\Models\AcademicTerm;
use App\Models\Campus;
use App\Models\Driver;
use App\Models\P2pPolicyTerm;
use App\Models\PolicyRoute;
use App\Models\PolicyStudent;
use App\Models\Student;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

/**
 * Dữ liệu mẫu P2P Policy: campus, kỳ nháp, tuyến, roster học sinh.
 * Chạy: php artisan db:seed --class=P2pPolicyDemoSeeder
 */
class P2pPolicyDemoSeeder extends Seeder
{
    private const ACADEMIC_YEAR = '2025-2026';

    private const TERM_CODE = 'HK1';

    /** @var list<array{route: string, code: string, name: string, class: string, direction: string, policy_type: string, active: bool}> */
    private const ROSTER = [
        ['route' => 'P2P Q7 → Tân Bình', 'code' => 'VA250001', 'name' => 'Nguyễn Minh An', 'class' => '6A', 'direction' => 'two_way', 'policy_type' => 'internal', 'active' => true],
        ['route' => 'P2P Q7 → Tân Bình', 'code' => 'VA250002', 'name' => 'Trần Bảo Khánh', 'class' => '6A', 'direction' => 'two_way', 'policy_type' => 'internal', 'active' => true],
        ['route' => 'P2P Q7 → Tân Bình', 'code' => 'VA250003', 'name' => 'Lê Phương Thảo', 'class' => '6A', 'direction' => 'one_way', 'policy_type' => 'internal', 'active' => true],
        ['route' => 'P2P Q7 → Tân Bình', 'code' => 'VA250004', 'name' => 'Phạm Đức Huy', 'class' => '6B', 'direction' => 'two_way', 'policy_type' => 'internal', 'active' => true],
        ['route' => 'P2P Q7 → Tân Bình', 'code' => 'VA250005', 'name' => 'Hoàng Ngọc Linh', 'class' => '6B', 'direction' => 'two_way', 'policy_type' => 'internal', 'active' => true],
        ['route' => 'P2P Q7 → Tân Bình', 'code' => 'VA250006', 'name' => 'Võ Thanh Tùng', 'class' => '7A', 'direction' => 'two_way', 'policy_type' => 'internal', 'active' => true],
        ['route' => 'P2P Q7 → Tân Bình', 'code' => 'VA250007', 'name' => 'Đặng Kim Ngân', 'class' => '7A', 'direction' => 'one_way', 'policy_type' => 'internal', 'active' => true],
        ['route' => 'P2P Q7 → Tân Bình', 'code' => 'VA250008', 'name' => 'Bùi Hữu Phát', 'class' => '7B', 'direction' => 'two_way', 'policy_type' => 'internal', 'active' => false],
        ['route' => 'P2P Tân Bình → Q7', 'code' => 'VA250101', 'name' => 'Ngô Gia Hân', 'class' => '8A', 'direction' => 'two_way', 'policy_type' => 'internal', 'active' => true],
        ['route' => 'P2P Tân Bình → Q7', 'code' => 'VA250102', 'name' => 'Dương Quốc Bảo', 'class' => '8A', 'direction' => 'two_way', 'policy_type' => 'internal', 'active' => true],
        ['route' => 'P2P Tân Bình → Q7', 'code' => 'VA250103', 'name' => 'Mai Thu Trang', 'class' => '8B', 'direction' => 'two_way', 'policy_type' => 'internal', 'active' => true],
        ['route' => 'P2P Tân Bình → Q7', 'code' => 'VA250104', 'name' => 'Lý Văn Đạt', 'class' => '8B', 'direction' => 'one_way', 'policy_type' => 'internal', 'active' => true],
        ['route' => 'P2P Tân Bình → Q7', 'code' => 'VA250105', 'name' => 'Châu Minh Quân', 'class' => '9A', 'direction' => 'two_way', 'policy_type' => 'internal', 'active' => true],
        ['route' => 'P2P Tân Bình → Q7', 'code' => 'VA250106', 'name' => 'Hồ Thị Mai', 'class' => '9A', 'direction' => 'two_way', 'policy_type' => 'internal', 'active' => true],
        ['route' => 'P2P Tân Bình → Q7', 'code' => 'VA250107', 'name' => 'Tăng Nhật Minh', 'class' => '9B', 'direction' => 'two_way', 'policy_type' => 'internal', 'active' => true],
        ['route' => 'P2P Tân Bình → Q7', 'code' => 'VA250108', 'name' => 'Vương Anh Thư', 'class' => '10A', 'direction' => 'two_way', 'policy_type' => 'default', 'active' => true],
    ];

    public function run(): void
    {
        $campusQ7 = Campus::updateOrCreate(
            ['code' => 'Q7'],
            ['name' => 'Campus Quận 7', 'is_active' => true],
        );
        $campusTb = Campus::updateOrCreate(
            ['code' => 'TB'],
            ['name' => 'Campus Tân Bình', 'is_active' => true],
        );

        $academicTerm = AcademicTerm::updateOrCreate(
            ['academic_year' => self::ACADEMIC_YEAR, 'term_code' => self::TERM_CODE],
            [
                'name' => 'Học kỳ 1',
                'starts_on' => '2025-08-01',
                'ends_on' => '2026-01-15',
                'is_active' => true,
            ],
        );

        $term = P2pPolicyTerm::updateOrCreate(
            [
                'academic_term_id' => $academicTerm->id,
                'operating_from' => '2025-08-11',
                'operating_to' => '2026-01-10',
            ],
            [
                'default_morning_start' => '06:15:00',
                'default_morning_end' => '07:15:00',
                'default_afternoon_start' => '15:30:00',
                'default_afternoon_end' => '16:30:00',
                'weekdays_mask' => 31,
                'exclude_fixed_holidays' => true,
                'status' => 'draft',
            ],
        );

        $vehicle = Vehicle::query()->where('license_plate', '51A-10015')->first();
        $driver = Driver::query()->where('employment_status', 'active')->orderBy('id')->first();

        $routeQ7Tb = PolicyRoute::updateOrCreate(
            [
                'p2p_policy_term_id' => $term->id,
                'name' => 'P2P Q7 → Tân Bình',
            ],
            [
                'origin_campus_id' => $campusQ7->id,
                'dest_campus_id' => $campusTb->id,
                'vehicle_id' => $vehicle?->id,
                'driver_id' => $driver?->id,
                'is_active' => true,
            ],
        );

        $routeTbQ7 = PolicyRoute::updateOrCreate(
            [
                'p2p_policy_term_id' => $term->id,
                'name' => 'P2P Tân Bình → Q7',
            ],
            [
                'origin_campus_id' => $campusTb->id,
                'dest_campus_id' => $campusQ7->id,
                'vehicle_id' => $vehicle?->id,
                'driver_id' => $driver?->id,
                'is_active' => true,
            ],
        );

        $routesByName = [
            'P2P Q7 → Tân Bình' => $routeQ7Tb,
            'P2P Tân Bình → Q7' => $routeTbQ7,
        ];

        $effectiveFrom = '2025-08-11';
        $importTag = 'P2pPolicyDemoSeeder';

        foreach (self::ROSTER as $row) {
            $route = $routesByName[$row['route']] ?? null;
            if ($route === null) {
                continue;
            }

            $student = Student::updateOrCreate(
                ['student_code' => $row['code']],
                [
                    'full_name' => $row['name'],
                    'grade' => $row['class'],
                    'is_active' => $row['active'],
                ],
            );

            PolicyStudent::updateOrCreate(
                [
                    'policy_route_id' => $route->id,
                    'student_id' => $student->id,
                    'effective_from' => $effectiveFrom,
                ],
                [
                    'student_code' => $row['code'],
                    'student_name' => $row['name'],
                    'class_name' => $row['class'],
                    'direction' => $row['direction'],
                    'policy_type' => $row['policy_type'],
                    'effective_to' => null,
                    'is_active' => $row['active'],
                    'imported_from' => $importTag,
                ],
            );
        }

        $this->command?->info(sprintf(
            'P2P Policy demo: kỳ #%d (%s), %d học sinh trên %d tuyến.',
            $term->id,
            self::ACADEMIC_YEAR.' '.self::TERM_CODE,
            count(self::ROSTER),
            count($routesByName),
        ));
    }
}
