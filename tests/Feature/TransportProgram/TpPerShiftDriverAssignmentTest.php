<?php

namespace Tests\Feature\TransportProgram;

use App\Models\Driver;
use App\Models\TpProgram;
use App\Models\TpProgramDay;
use App\Models\User;
use App\Services\TransportProgram\DriverAssignmentService;
use App\Services\TransportProgram\TpProgramPresenter;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TpPerShiftDriverAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_morning_and_afternoon_main_drivers_are_independent(): void
    {
        $this->seed(RbacSeeder::class);

        $driverAm = $this->makeDriver('TX Sáng');
        $driverPm = $this->makeDriver('TX Chiều');

        $date = now('Asia/Ho_Chi_Minh')->addDays(3)->toDateString();

        $program = TpProgram::query()->create([
            'code' => 'TP-SHIFT',
            'name' => 'Đưa đón 2 ca',
            'status' => 'active',
            'start_date' => $date,
            'end_date' => $date,
            'departure_time' => '06:00',
            'return_time' => '17:00',
            'runs_on' => ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'],
            'settings' => [
                'morning' => ['enabled' => true, 'departure' => '06:00', 'arrival' => '07:00'],
                'afternoon' => ['enabled' => true, 'departure' => '17:00', 'arrival' => '18:00'],
            ],
        ]);

        $day = TpProgramDay::query()->create([
            'program_id' => $program->id,
            'scheduled_date' => $date,
            'day_type' => TpProgramDay::DAY_OPERATING,
            'expected_count' => 10,
        ]);

        $svc = app(DriverAssignmentService::class);
        $svc->assignDriver($day, (int) $driverAm->id, null, null, 'morning');
        $svc->assignDriver($day->fresh(), (int) $driverPm->id, null, null, 'afternoon');

        $payload = app(TpProgramPresenter::class)->programDay($day->fresh());
        $this->assertTrue($payload['uses_per_shift_drivers']);
        $this->assertSame($driverAm->id, $payload['shift_assignments']['morning']['effective_driver']['id']);
        $this->assertSame($driverPm->id, $payload['shift_assignments']['afternoon']['effective_driver']['id']);
    }

    public function test_per_day_override_driver_beats_program_shift_default(): void
    {
        $this->seed(RbacSeeder::class);

        $defaultAm = $this->makeDriver('TX Mặc định Sáng');
        $defaultPm = $this->makeDriver('TX Mặc định Chiều');
        $perDay = $this->makeDriver('TX Theo ngày');

        $date = now('Asia/Ho_Chi_Minh')->addDays(3)->toDateString();

        $program = TpProgram::query()->create([
            'code' => 'TP-OVERRIDE',
            'name' => 'Đưa đón 2 ca có mặc định ca',
            'status' => 'active',
            'start_date' => $date,
            'end_date' => $date,
            'departure_time' => '06:00',
            'return_time' => '17:00',
            'runs_on' => ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'],
            'settings' => [
                'morning' => ['enabled' => true, 'departure' => '06:00', 'default_driver_id' => $defaultAm->id],
                'afternoon' => ['enabled' => true, 'departure' => '17:00', 'default_driver_id' => $defaultPm->id],
            ],
        ]);

        // Override theo ngày dạng chung (cột driver_id) — ví dụ dữ liệu cũ trước khi tách ca,
        // hoặc phân công khi chương trình còn một ca. Phải thắng tài xế mặc định của ca.
        $day = TpProgramDay::query()->create([
            'program_id' => $program->id,
            'scheduled_date' => $date,
            'day_type' => TpProgramDay::DAY_OPERATING,
            'expected_count' => 10,
            'driver_id' => $perDay->id,
        ]);

        $support = app(\App\Services\TransportProgram\TpShiftDriverSupport::class);
        $this->assertSame($perDay->id, $support->effectiveMainDriverId($day, 'morning'));
        $this->assertSame($perDay->id, $support->effectiveMainDriverId($day, 'afternoon'));

        $payload = app(TpProgramPresenter::class)->programDay($day->fresh());
        $this->assertSame($perDay->id, $payload['shift_assignments']['morning']['effective_driver']['id']);
        $this->assertSame($perDay->id, $payload['shift_assignments']['afternoon']['effective_driver']['id']);
    }

    public function test_effective_driver_payload_exposes_avatar_url(): void
    {
        $this->seed(RbacSeeder::class);

        $driver = $this->makeDriver('TX Có Ảnh', avatarUrl: 'https://example.test/avatar.png');

        $date = now('Asia/Ho_Chi_Minh')->addDays(3)->toDateString();

        $program = TpProgram::query()->create([
            'code' => 'TP-AVATAR',
            'name' => 'Đưa đón 2 ca',
            'status' => 'active',
            'start_date' => $date,
            'end_date' => $date,
            'departure_time' => '06:00',
            'return_time' => '17:00',
            'runs_on' => ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'],
            'settings' => [
                'morning' => ['enabled' => true, 'departure' => '06:00'],
                'afternoon' => ['enabled' => true, 'departure' => '17:00'],
            ],
        ]);

        $day = TpProgramDay::query()->create([
            'program_id' => $program->id,
            'scheduled_date' => $date,
            'day_type' => TpProgramDay::DAY_OPERATING,
            'expected_count' => 10,
            'morning_driver_id' => $driver->id,
        ]);

        $payload = app(TpProgramPresenter::class)->programDay($day->fresh());
        $this->assertSame(
            'https://example.test/avatar.png',
            $payload['shift_assignments']['morning']['effective_driver']['avatar_url'],
        );
    }

    private function makeDriver(string $name, ?string $avatarUrl = null): Driver
    {
        $user = User::factory()->create(['is_active' => true, 'avatar_url' => $avatarUrl]);
        $user->assignRole('driver');

        return Driver::query()->create([
            'user_id' => $user->id,
            'full_name' => $name,
            'phone' => '0900'.random_int(100000, 999999),
            'employment_status' => 'active',
            'availability_status' => 'available',
        ]);
    }
}
