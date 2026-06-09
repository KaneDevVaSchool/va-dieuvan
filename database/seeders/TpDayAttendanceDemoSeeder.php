<?php

namespace Database\Seeders;

use App\Actions\CreateTransportProgramAction;
use App\Models\Driver;
use App\Models\TpEnrollment;
use App\Models\TpProgram;
use App\Models\TpProgramDay;
use App\Models\TpStudent;
use App\Models\TpTripStudentLog;
use App\Services\TransportProgram\AttendanceService;
use App\Services\TransportProgram\ProgramEnrollmentService;
use App\Services\TransportProgram\TripExecutionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Dữ liệu demo cho màn điểm danh theo ngày (/mng/transport-program-days/{id}/attendance?shift=morning).
 *
 * php artisan db:seed --class=TpDayAttendanceDemoSeeder
 *
 * Biến môi trường (tùy chọn):
 * - TP_ATTENDANCE_DEMO_DAY_ID=1  — ngày chương trình cần seed (mặc định 1)
 * - ALLOW_TP_ATTENDANCE_DEMO_SEED=1 — cho phép chạy ngoài local/testing
 */
class TpDayAttendanceDemoSeeder extends Seeder
{
    private const PROGRAM_CODE = 'DEMO-TP-ATTENDANCE';

    /** @var list<array{code: string, full_name: string, grade: string, class_name: string, parent_phone: string, pickup_point: string}> */
    private const STUDENTS = [
        ['code' => 'DEMO-ATT-001', 'full_name' => 'Nguyễn Minh An', 'grade' => '6', 'class_name' => '6A1', 'parent_phone' => '0901000001', 'pickup_point' => 'Cổng chính — đường Lê Lợi'],
        ['code' => 'DEMO-ATT-002', 'full_name' => 'Trần Thu Hà', 'grade' => '6', 'class_name' => '6A1', 'parent_phone' => '0901000002', 'pickup_point' => 'Cổng chính — đường Lê Lợi'],
        ['code' => 'DEMO-ATT-003', 'full_name' => 'Lê Quốc Bảo', 'grade' => '6', 'class_name' => '6B2', 'parent_phone' => '0901000003', 'pickup_point' => 'Chợ Bà Chiểu'],
        ['code' => 'DEMO-ATT-004', 'full_name' => 'Phạm Ngọc Linh', 'grade' => '7', 'class_name' => '7A3', 'parent_phone' => '0901000004', 'pickup_point' => 'Siêu thị Co.opmart'],
        ['code' => 'DEMO-ATT-005', 'full_name' => 'Hoàng Đức Phúc', 'grade' => '7', 'class_name' => '7A3', 'parent_phone' => '0901000005', 'pickup_point' => 'Siêu thị Co.opmart'],
        ['code' => 'DEMO-ATT-006', 'full_name' => 'Võ Thị Mai', 'grade' => '8', 'class_name' => '8C1', 'parent_phone' => '0901000006', 'pickup_point' => 'Ngã tư Hàng Xanh'],
        ['code' => 'DEMO-ATT-007', 'full_name' => 'Đặng Khánh Vy', 'grade' => '8', 'class_name' => '8C2', 'parent_phone' => '0901000007', 'pickup_point' => 'Ngã tư Hàng Xanh'],
        ['code' => 'DEMO-ATT-008', 'full_name' => 'Bùi Hữu Tài', 'grade' => '9', 'class_name' => '9A1', 'parent_phone' => '0901000008', 'pickup_point' => 'Cổng phụ — đường Xô Viết'],
        ['code' => 'DEMO-ATT-009', 'full_name' => 'Ngô Thanh Tùng', 'grade' => '9', 'class_name' => '9A2', 'parent_phone' => '0901000009', 'pickup_point' => 'Cổng phụ — đường Xô Viết'],
        ['code' => 'DEMO-ATT-010', 'full_name' => 'Dương Bích Ngọc', 'grade' => '10', 'class_name' => '10A1', 'parent_phone' => '0901000010', 'pickup_point' => 'Bến xe Miền Đông'],
        ['code' => 'DEMO-ATT-011', 'full_name' => 'Lý Gia Hân', 'grade' => '10', 'class_name' => '10B1', 'parent_phone' => '0901000011', 'pickup_point' => 'Bến xe Miền Đông'],
        ['code' => 'DEMO-ATT-012', 'full_name' => 'Mai Tuấn Kiệt', 'grade' => '11', 'class_name' => '11A1', 'parent_phone' => '0901000012', 'pickup_point' => 'Chung cư Sunrise'],
    ];

    public function run(): void
    {
        if (! $this->allowedToRun()) {
            $this->command?->warn('TpDayAttendanceDemoSeeder: chỉ chạy trên local/testing hoặc khi ALLOW_TP_ATTENDANCE_DEMO_SEED=1.');

            return;
        }

        $this->call(TpAbsenceReasonSeeder::class);

        $targetDayId = (int) env('TP_ATTENDANCE_DEMO_DAY_ID', 1);
        $day = TpProgramDay::query()->with('program')->find($targetDayId);

        if (! $day) {
            $day = $this->createDemoProgramDay($targetDayId);
        }

        $this->configureProgramAndDay($day);
        $studentIds = $this->ensureDemoStudents();
        $this->enrollStudents($day->program, $studentIds);
        $this->seedMorningAttendanceSamples($day->fresh(), $studentIds);
        $this->seedBoardedTimeSpread($day->fresh(), $this->demoDriver());

        $day = $day->fresh();
        $url = url("/mng/transport-program-days/{$day->id}/attendance?shift=morning");

        $this->command?->info("TpDayAttendanceDemoSeeder: day_id={$day->id}, program=\"{$day->program?->name}\", date={$day->scheduled_date->toDateString()}");
        $this->command?->info("Mở: {$url}");
    }

    private function allowedToRun(): bool
    {
        if (filter_var(env('ALLOW_TP_ATTENDANCE_DEMO_SEED', false), FILTER_VALIDATE_BOOL)) {
            return true;
        }

        return app()->environment(['local', 'testing']);
    }

    private function createDemoProgramDay(int $targetDayId): TpProgramDay
    {
        $driver = $this->demoDriver();
        $today = Carbon::today();

        $program = TpProgram::query()->where('code', self::PROGRAM_CODE)->first();
        if (! $program) {
            $result = app(CreateTransportProgramAction::class)->execute([
                'code' => self::PROGRAM_CODE,
                'name' => 'CT điểm danh demo',
                'departure_time' => '06:00:00',
                'return_time' => '16:30:00',
                'start_date' => $today->copy()->subDays(3)->toDateString(),
                'end_date' => $today->copy()->addDays(14)->toDateString(),
                'runs_on' => ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'],
                'default_driver_id' => $driver->id,
                'settings' => $this->twoShiftSettings(),
            ], null);

            $program = TpProgram::query()->findOrFail($result['program']['id']);
        }

        $program->update([
            'status' => TpProgram::STATUS_ACTIVE,
            'default_driver_id' => $driver->id,
            'settings' => $this->twoShiftSettings(),
        ]);

        $day = $program->days()
            ->whereDate('scheduled_date', $today)
            ->first();

        if (! $day) {
            $day = $program->days()->orderBy('scheduled_date')->first();
        }

        abort_if($day === null, 500, 'Không tạo được ngày vận hành cho chương trình demo.');

        if ($day->id !== $targetDayId) {
            $this->command?->warn("Không có tp_program_days.id={$targetDayId}; dùng day_id={$day->id}. Đặt TP_ATTENDANCE_DEMO_DAY_ID={$day->id} hoặc migrate:fresh nếu cần đúng id=1.");
        }

        return $day;
    }

    private function configureProgramAndDay(TpProgramDay $day): void
    {
        $program = $day->program;
        abort_if($program === null, 500, 'Ngày chương trình không có program.');

        $driver = $this->demoDriver();

        $program->update([
            'status' => TpProgram::STATUS_ACTIVE,
            'default_driver_id' => $driver->id,
            'settings' => $this->twoShiftSettings(),
        ]);

        $today = Carbon::today()->toDateString();
        if ($day->scheduled_date->toDateString() !== $today) {
            $conflict = TpProgramDay::query()
                ->where('program_id', $program->id)
                ->whereDate('scheduled_date', $today)
                ->where('id', '!=', $day->id)
                ->exists();

            if (! $conflict) {
                $day->update(['scheduled_date' => $today]);
            }
        }

        $day->update([
            'day_type' => TpProgramDay::DAY_OPERATING,
            'driver_id' => $driver->id,
            'morning_driver_id' => $driver->id,
            'afternoon_driver_id' => $driver->id,
            'morning_attendance_status' => AttendanceService::STATUS_DRAFT,
            'afternoon_attendance_status' => AttendanceService::STATUS_NOT_STARTED,
            'attendance_status' => AttendanceService::STATUS_DRAFT,
        ]);
    }

    /** @return list<int> */
    private function ensureDemoStudents(): array
    {
        $ids = [];

        foreach (self::STUDENTS as $row) {
            $student = TpStudent::query()->updateOrCreate(
                ['code' => $row['code']],
                [
                    'full_name' => $row['full_name'],
                    'grade' => $row['grade'],
                    'class_name' => $row['class_name'],
                    'parent_phone' => $row['parent_phone'],
                    'status' => TpStudent::STATUS_ACTIVE,
                ]
            );
            $ids[] = $student->id;
        }

        return $ids;
    }

    /** @param  list<int>  $studentIds */
    private function enrollStudents(TpProgram $program, array $studentIds): void
    {
        app(ProgramEnrollmentService::class)->enrollBulk($program, $studentIds, null);

        foreach (self::STUDENTS as $row) {
            $student = TpStudent::query()->where('code', $row['code'])->first();
            if (! $student) {
                continue;
            }
            TpEnrollment::query()
                ->where('program_id', $program->id)
                ->where('student_id', $student->id)
                ->whereNull('unenrolled_at')
                ->update(['pickup_point' => $row['pickup_point']]);
        }

        $count = $program->enrollments()->whereNull('unenrolled_at')->count();
        TpProgramDay::query()
            ->where('program_id', $program->id)
            ->where('day_type', TpProgramDay::DAY_OPERATING)
            ->whereDoesntHave('execution')
            ->update(['expected_count' => $count]);
    }

    /** @param  list<int>  $studentIds */
    private function seedMorningAttendanceSamples(TpProgramDay $day, array $studentIds): void
    {
        $attendance = app(AttendanceService::class);

        $byCode = TpStudent::query()->whereIn('id', $studentIds)->pluck('id', 'code');

        $excusedId = $byCode->get('DEMO-ATT-003');
        $unexcusedMissingReasonId = $byCode->get('DEMO-ATT-007');
        $unexcusedWithReasonId = $byCode->get('DEMO-ATT-009');

        if ($excusedId) {
            $attendance->markAbsent(
                $day,
                (int) $excusedId,
                'parent_notified',
                null,
                null,
                'dispatcher',
                'excused',
                'sick',
                false,
                'morning',
            );
        }

        if ($unexcusedWithReasonId) {
            $attendance->markAbsent(
                $day,
                (int) $unexcusedWithReasonId,
                'no_notice',
                null,
                null,
                'dispatcher',
                'unexcused',
                'no_notice',
                false,
                'morning',
            );
        }

        if ($unexcusedMissingReasonId) {
            $attendance->markAbsent(
                $day,
                (int) $unexcusedMissingReasonId,
                'no_notice',
                null,
                null,
                'dispatcher',
                'unexcused',
                null,
                false,
                'morning',
            );
        }

        $day->update([
            'morning_attendance_status' => AttendanceService::STATUS_DRAFT,
        ]);
    }

    /** Giờ lên xe rải từ 06:02–06:58 để test bộ lọc khoảng giờ trên màn điểm danh. */
    private function seedBoardedTimeSpread(TpProgramDay $day, Driver $driver): void
    {
        $execution = $day->execution()->first();
        if (! $execution) {
            try {
                $execution = app(TripExecutionService::class)->start($day->fresh(), $driver);
            } catch (\Throwable $e) {
                $this->command?->warn('TpDayAttendanceDemoSeeder: không tạo được chuyến demo — '.$e->getMessage());

                return;
            }
        }

        $date = $day->scheduled_date->toDateString();
        $times = ['06:02', '06:08', '06:15', '06:22', '06:30', '06:38', '06:45', '06:52', '06:58'];

        $logs = $execution->studentLogs()
            ->where('final_status', '!=', TpTripStudentLog::FINAL_ABSENT)
            ->orderBy('student_id')
            ->get();

        foreach ($logs as $index => $log) {
            $hm = $times[$index % count($times)];
            $boardedAt = Carbon::parse("{$date} {$hm}:00", config('app.timezone'));
            $log->update([
                'initial_status' => 'boarded',
                'final_status' => TpTripStudentLog::FINAL_BOARDED,
                'boarded_at' => $boardedAt,
            ]);
        }

        $execution->update(['total_boarded' => $logs->count()]);

        $this->command?->info("TpDayAttendanceDemoSeeder: đã gán giờ lên xe cho {$logs->count()} học sinh (khoảng 06:02–06:58).");
    }

    private function demoDriver(): Driver
    {
        return Driver::query()->firstOrCreate(
            ['full_name' => 'Bùi Trung Hiếu'],
            [
                'employment_status' => 'active',
                'availability_status' => 'available',
            ]
        );
    }

    /** @return array<string, mixed> */
    private function twoShiftSettings(): array
    {
        return [
            'morning' => ['enabled' => true, 'departure' => '06:00', 'arrival' => '07:00'],
            'afternoon' => ['enabled' => true, 'departure' => '16:30', 'arrival' => '17:30'],
        ];
    }
}
