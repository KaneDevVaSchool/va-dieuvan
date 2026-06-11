<?php

namespace Tests\Feature\TransportProgram;

use App\Actions\CreateTransportProgramAction;
use App\Models\Driver;
use App\Models\TpProgram;
use App\Models\TpStudent;
use App\Models\User;
use App\Services\TransportProgram\AttendanceService;
use App\Services\TransportProgram\ProgramEnrollmentService;
use App\Services\TransportProgram\StudentLogService;
use App\Services\TransportProgram\TripExecutionService;
use Database\Seeders\RbacSeeder;
use Database\Seeders\TpAbsenceReasonSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TpAttendanceSubsystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(TpAbsenceReasonSeeder::class);
    }

    private function dispatcher(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('dispatcher');

        return $user;
    }

    private function programWithStudents(): array
    {
        $driver = Driver::create(['full_name' => 'TX Test']);
        $result = app(CreateTransportProgramAction::class)->execute([
            'name' => 'CT điểm danh',
            'departure_time' => '06:30',
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->addDays(2)->toDateString(),
            'runs_on' => ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'],
            'default_driver_id' => $driver->id,
        ], null);

        $program = TpProgram::findOrFail($result['program']['id']);
        $s1 = TpStudent::create(['code' => 'AD001', 'full_name' => 'HS A', 'status' => 'active', 'class_name' => '6A']);
        $s2 = TpStudent::create(['code' => 'AD002', 'full_name' => 'HS B', 'status' => 'active', 'class_name' => '6B']);
        app(ProgramEnrollmentService::class)->enrollBulk($program, [$s1->id, $s2->id], null);
        $day = $program->days()->orderBy('scheduled_date')->first();

        return compact('program', 'day', 's1', 's2', 'driver');
    }

    public function test_get_attendance_summary_and_confirm_blocks_missing_reason(): void
    {
        ['day' => $day, 's1' => $s1] = $this->programWithStudents();
        $attendance = app(AttendanceService::class);

        $attendance->markAbsent($day, $s1->id, 'no_notice', null, null, 'dispatcher', 'unexcused', null);
        $payload = $attendance->getAttendance($day->fresh());

        $this->assertSame(2, $payload['summary']['total']);
        $this->assertSame(1, $payload['summary']['present']);
        $this->assertSame(1, $payload['summary']['unexcused']);
        $this->assertSame(1, $payload['missing_reason_count']);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $attendance->confirmAttendance($day->fresh(), 0, null);
    }

    public function test_confirm_with_reason_and_lock_version(): void
    {
        ['day' => $day, 's1' => $s1] = $this->programWithStudents();
        $attendance = app(AttendanceService::class);
        $attendance->markAbsent($day, $s1->id, 'no_notice', null, null, 'dispatcher', 'unexcused', 'no_notice');

        $confirmed = $attendance->confirmAttendance($day->fresh(), 0, null);
        $this->assertSame('confirmed', $confirmed->attendance_status);
        $this->assertSame(1, (int) $confirmed->attendance_lock_version);
    }

    public function test_driver_mark_absent_syncs_expected_count(): void
    {
        ['day' => $day, 's2' => $s2, 'driver' => $driver] = $this->programWithStudents();
        $before = (int) $day->fresh()->expected_count;

        $execution = app(TripExecutionService::class)->start($day->fresh(), $driver);
        $log = $execution->studentLogs()->where('student_id', $s2->id)->first();
        app(StudentLogService::class)->markAbsent($log, 'no_notice', null, null);

        $after = (int) $day->fresh()->expected_count;
        $this->assertSame($before - 1, $after);
    }

    public function test_confirm_returns_409_on_stale_lock_version(): void
    {
        $user = $this->dispatcher();
        ['day' => $day, 's1' => $s1] = $this->programWithStudents();

        $this->actingAs($user)
            ->postJson("/api/tp-program-days/{$day->id}/absences", [
                'student_ids' => [$s1->id],
                'absence_type' => 'parent_notified',
                'category' => 'excused',
                'reason_code' => 'sick',
            ])
            ->assertOk();

        $this->actingAs($user)
            ->postJson("/api/tp-program-days/{$day->id}/attendance/confirm", [
                'attendance_lock_version' => 99,
            ])
            ->assertStatus(409);
    }

    public function test_api_mark_absent_and_confirm(): void
    {
        $user = $this->dispatcher();
        ['day' => $day, 's1' => $s1] = $this->programWithStudents();

        $this->actingAs($user)
            ->postJson("/api/tp-program-days/{$day->id}/absences", [
                'student_ids' => [$s1->id],
                'absence_type' => 'parent_notified',
                'category' => 'excused',
                'reason_code' => 'sick',
            ])
            ->assertOk()
            ->assertJsonPath('data.summary.excused', 1);

        $this->actingAs($user)
            ->postJson("/api/tp-program-days/{$day->id}/attendance/confirm", [
                'attendance_lock_version' => 0,
            ])
            ->assertOk()
            ->assertJsonPath('data.attendance_status', 'confirmed');
    }

    public function test_morning_and_afternoon_attendance_are_independent(): void
    {
        $driver = Driver::create(['full_name' => 'TX 2 ca']);
        $result = app(CreateTransportProgramAction::class)->execute([
            'name' => 'CT 2 ca',
            'departure_time' => '06:30',
            'return_time' => '17:00',
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->addDays(2)->toDateString(),
            'runs_on' => ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'],
            'default_driver_id' => $driver->id,
            'settings' => [
                'morning' => ['enabled' => true, 'departure' => '06:30', 'arrival' => '07:30'],
                'afternoon' => ['enabled' => true, 'departure' => '17:00', 'arrival' => '18:00'],
            ],
        ], null);

        $program = TpProgram::findOrFail($result['program']['id']);
        $s1 = TpStudent::create(['code' => 'AD010', 'full_name' => 'HS 2ca', 'status' => 'active', 'class_name' => '6A']);
        app(ProgramEnrollmentService::class)->enrollBulk($program, [$s1->id], null);
        $day = $program->days()->orderBy('scheduled_date')->first();

        $attendance = app(AttendanceService::class);
        $attendance->markAbsent($day, $s1->id, 'no_notice', null, null, 'dispatcher', 'unexcused', 'no_notice', true, 'morning');

        $morning = $attendance->getAttendance($day->fresh(), 'morning');
        $afternoon = $attendance->getAttendance($day->fresh(), 'afternoon');

        $this->assertTrue($morning['multi_slot']);
        $this->assertSame('morning', $morning['shift']);
        $this->assertSame(0, $morning['summary']['present']);
        $this->assertSame(1, $afternoon['summary']['present']);
    }

    public function test_boarded_at_is_scoped_to_attendance_shift(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-11 06:15:00'));

        $driver = Driver::create(['full_name' => 'TX boarded']);
        $result = app(CreateTransportProgramAction::class)->execute([
            'name' => 'CT boarded ca',
            'departure_time' => '06:30',
            'return_time' => '17:00',
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->addDays(2)->toDateString(),
            'runs_on' => ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'],
            'default_driver_id' => $driver->id,
            'settings' => [
                'morning' => ['enabled' => true, 'departure' => '06:30', 'arrival' => '07:30'],
                'afternoon' => ['enabled' => true, 'departure' => '17:00', 'arrival' => '18:00'],
            ],
        ], null);

        $program = TpProgram::findOrFail($result['program']['id']);
        $s1 = TpStudent::create(['code' => 'AD011', 'full_name' => 'HS boarded', 'status' => 'active', 'class_name' => '6A']);
        app(ProgramEnrollmentService::class)->enrollBulk($program, [$s1->id], null);
        $day = $program->days()->orderBy('scheduled_date')->first();

        $morningExec = app(TripExecutionService::class)->start($day->fresh(), $driver, null, 'morning');
        $morningLog = $morningExec->studentLogs()->where('student_id', $s1->id)->first();
        app(StudentLogService::class)->board($morningLog, null);

        $attendance = app(AttendanceService::class);
        $morningPayload = $attendance->getAttendance($day->fresh(), 'morning');
        $afternoonPayload = $attendance->getAttendance($day->fresh(), 'afternoon');

        $morningItem = collect($morningPayload['items'])->firstWhere('student_id', $s1->id);
        $afternoonItem = collect($afternoonPayload['items'])->firstWhere('student_id', $s1->id);

        $this->assertNotEmpty($morningItem['boarded_at']);
        $this->assertNull($afternoonItem['boarded_at']);

        app(TripExecutionService::class)->complete($morningExec->fresh(), true, null);

        Carbon::setTestNow(Carbon::parse('2026-06-11 17:05:00'));
        $afternoonExec = app(TripExecutionService::class)->start($day->fresh(), $driver, null, 'afternoon');
        $afternoonLog = $afternoonExec->studentLogs()->where('student_id', $s1->id)->first();
        app(StudentLogService::class)->board($afternoonLog, null);

        $morningAfter = $attendance->getAttendance($day->fresh(), 'morning');
        $afternoonAfter = $attendance->getAttendance($day->fresh(), 'afternoon');
        $morningItemAfter = collect($morningAfter['items'])->firstWhere('student_id', $s1->id);
        $afternoonItemAfter = collect($afternoonAfter['items'])->firstWhere('student_id', $s1->id);

        $this->assertStringContainsString('06:15', Carbon::parse($morningItemAfter['boarded_at'])->format('H:i'));
        $this->assertStringContainsString('17:05', Carbon::parse($afternoonItemAfter['boarded_at'])->format('H:i'));

        Carbon::setTestNow();
    }

    public function test_boarded_at_still_visible_on_attendance_after_driver_alights(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-03 06:20:00'));

        $driver = Driver::create(['full_name' => 'TX alight']);
        $result = app(CreateTransportProgramAction::class)->execute([
            'name' => 'CT alight attendance',
            'departure_time' => '06:30',
            'return_time' => '17:00',
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->toDateString(),
            'runs_on' => ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'],
            'default_driver_id' => $driver->id,
            'settings' => [
                'morning' => ['enabled' => true, 'departure' => '06:30', 'arrival' => '07:30'],
                'afternoon' => ['enabled' => true, 'departure' => '17:00', 'arrival' => '18:00'],
            ],
        ], null);

        $program = TpProgram::findOrFail($result['program']['id']);
        $student = TpStudent::create(['code' => 'AL001', 'full_name' => 'HS alight', 'status' => 'active', 'class_name' => '6A']);
        app(ProgramEnrollmentService::class)->enrollBulk($program, [$student->id], null);
        $day = $program->days()->orderBy('scheduled_date')->first();

        $execution = app(TripExecutionService::class)->start($day->fresh(), $driver, null, 'morning');
        $log = $execution->studentLogs()->where('student_id', $student->id)->firstOrFail();
        $logService = app(StudentLogService::class);
        $logService->board($log, null);
        $logService->alight($log->fresh(), null);

        $item = collect(app(AttendanceService::class)->getAttendance($day->fresh(), 'morning')['items'])
            ->firstWhere('student_id', $student->id);

        $this->assertNotEmpty($item['boarded_at']);
        $this->assertStringContainsString('06:20', Carbon::parse($item['boarded_at'])->format('H:i'));

        Carbon::setTestNow();
    }

    public function test_board_rejects_when_student_already_boarded_or_alighted(): void
    {
        $driver = Driver::create(['full_name' => 'TX guard']);
        $result = app(CreateTransportProgramAction::class)->execute([
            'name' => 'CT guard board',
            'departure_time' => '06:30',
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->toDateString(),
            'runs_on' => ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'],
            'default_driver_id' => $driver->id,
        ], null);

        $program = TpProgram::findOrFail($result['program']['id']);
        $student = TpStudent::create(['code' => 'BG001', 'full_name' => 'HS guard', 'status' => 'active', 'class_name' => '6A']);
        app(ProgramEnrollmentService::class)->enrollBulk($program, [$student->id], null);
        $day = $program->days()->orderBy('scheduled_date')->first();
        $execution = app(TripExecutionService::class)->start($day->fresh(), $driver, null, null);
        $log = $execution->studentLogs()->where('student_id', $student->id)->firstOrFail();
        $service = app(StudentLogService::class);

        $service->board($log->fresh(), null);

        $again = $service->board($log->fresh(), null);
        $this->assertSame(TpTripStudentLog::FINAL_BOARDED, $again->final_status);

        $service->alight($log->fresh(), null);

        try {
            $service->board($log->fresh(), null);
            $this->fail('Expected 422 when boarding after alight');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertSame(422, $e->getStatusCode());
            $this->assertStringContainsString('xuống xe', $e->getMessage());
        }
    }
}
