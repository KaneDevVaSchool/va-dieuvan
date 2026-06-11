<?php

namespace Tests\Feature\TransportProgram;

use App\Actions\CreateTransportProgramAction;
use App\Models\Driver;
use App\Models\TpProgram;
use App\Models\TpStudent;
use App\Models\TpTripExecution;
use App\Models\TpTripStudentLog;
use App\Services\TransportProgram\AttendanceService;
use App\Services\TransportProgram\ProgramEnrollmentService;
use App\Services\TransportProgram\StudentLogService;
use App\Services\TransportProgram\TripExecutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Phase 10 — luồng tài xế: bắt đầu chuyến seed logs, lên xe tăng đếm, hoàn thành.
 */
class TpExecutionFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_start_board_complete_flow(): void
    {
        $driver = Driver::create(['full_name' => 'Tài xế A']);

        $result = app(CreateTransportProgramAction::class)->execute([
            'name' => 'CT có tài xế',
            'departure_time' => '06:30',
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->addDays(2)->toDateString(),
            'runs_on' => ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'],
            'default_driver_id' => $driver->id,
        ], null);

        $program = TpProgram::findOrFail($result['program']['id']);

        $s1 = TpStudent::create(['code' => 'EX001', 'full_name' => 'HS 1', 'status' => 'active']);
        $s2 = TpStudent::create(['code' => 'EX002', 'full_name' => 'HS 2', 'status' => 'active']);
        app(ProgramEnrollmentService::class)->enrollBulk($program, [$s1->id, $s2->id], null);

        $day = $program->days()->orderBy('scheduled_date')->first();

        $execService = app(TripExecutionService::class);
        $execution = $execService->start($day, $driver);

        $this->assertSame(TpTripExecution::STATUS_IN_PROGRESS, $execution->status);
        $this->assertSame(2, $execution->studentLogs()->count());
        $this->assertSame(2, (int) $execution->total_expected);

        $logService = app(StudentLogService::class);
        $log1 = $execution->studentLogs()->where('student_id', $s1->id)->first();
        $logService->board($log1, null);
        $logService->alight($log1->fresh(), null);

        $log2 = $execution->studentLogs()->where('student_id', $s2->id)->first();
        $logService->markAbsent($log2, 'no_notice', null, null);

        $execution->refresh();
        $this->assertSame(1, (int) $execution->total_boarded);
        $this->assertSame(1, (int) $execution->total_alighted);
        $this->assertSame(1, (int) $execution->total_absent);

        $completed = $execService->complete($execution->fresh(), true, null);
        $this->assertSame(TpTripExecution::STATUS_COMPLETED, $completed->status);
        $this->assertSame(0, $completed->studentLogs()->where('final_status', TpTripStudentLog::FINAL_PENDING)->count());
    }

    public function test_board_after_driver_marks_boarded_student_absent_does_not_underflow_total_absent(): void
    {
        $driver = Driver::create(['full_name' => 'Tài xế vắng sau lên']);

        $result = app(CreateTransportProgramAction::class)->execute([
            'name' => 'CT vắng sau lên',
            'departure_time' => '06:30',
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->toDateString(),
            'runs_on' => ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'],
            'default_driver_id' => $driver->id,
        ], null);

        $program = TpProgram::findOrFail($result['program']['id']);
        $student = TpStudent::create(['code' => 'EX003', 'full_name' => 'HS 3', 'status' => 'active']);
        app(ProgramEnrollmentService::class)->enrollBulk($program, [$student->id], null);

        $day = $program->days()->orderBy('scheduled_date')->first();
        $execution = app(TripExecutionService::class)->start($day, $driver);
        $log = $execution->studentLogs()->where('student_id', $student->id)->firstOrFail();

        $logService = app(StudentLogService::class);
        $logService->board($log, null);
        $logService->markAbsent($log->fresh(), 'no_notice', null, null);

        $execution->refresh();
        $this->assertSame(0, (int) $execution->total_boarded);
        $this->assertSame(1, (int) $execution->total_absent);

        $logService->board($log->fresh(), null);

        $execution->refresh();
        $this->assertSame(1, (int) $execution->total_boarded);
        $this->assertSame(0, (int) $execution->total_absent);
    }

    public function test_driver_can_save_student_notes_during_trip(): void
    {
        $driver = Driver::create(['full_name' => 'Tài xế ghi chú']);

        $result = app(CreateTransportProgramAction::class)->execute([
            'name' => 'CT ghi chú',
            'departure_time' => '06:30',
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->addDays(2)->toDateString(),
            'runs_on' => ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'],
            'default_driver_id' => $driver->id,
        ], null);

        $program = TpProgram::findOrFail($result['program']['id']);
        $student = TpStudent::create(['code' => 'NT001', 'full_name' => 'HS ghi chú', 'status' => 'active']);
        app(ProgramEnrollmentService::class)->enrollBulk($program, [$student->id], null);

        $day = $program->days()->orderBy('scheduled_date')->first();
        $execution = app(TripExecutionService::class)->start($day, $driver);
        $log = $execution->studentLogs()->where('student_id', $student->id)->firstOrFail();

        $updated = app(StudentLogService::class)->updateDriverNotes($log, 'Hư quậy trên xe', null);
        $this->assertSame('Hư quậy trên xe', $updated->driver_notes);

        $attendance = app(AttendanceService::class)->getAttendance($day);
        $row = collect($attendance['items'])->firstWhere('student_id', $student->id);
        $this->assertSame('Hư quậy trên xe', $row['driver_notes']);
    }
}
