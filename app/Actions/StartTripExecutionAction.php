<?php

namespace App\Actions;

use App\Models\Driver;
use App\Models\TpDayAbsence;
use App\Models\TpEnrollment;
use App\Models\TpProgramDay;
use App\Models\TpTripExecution;
use App\Models\TpTripStudentLog;
use App\Services\TransportProgram\DriverAssignmentService;
use App\Services\TransportProgram\TpAuditLogger;
use App\Services\TransportProgram\TpProgramPresenter;
use App\Services\TransportProgram\TpProgramScheduleSlots;
use App\Services\TransportProgram\TpShiftDriverSupport;
use Illuminate\Support\Facades\DB;

class StartTripExecutionAction
{
    public function __construct(
        private readonly DriverAssignmentService $driverAssignment,
        private readonly TpAuditLogger $audit,
        private readonly TpProgramPresenter $presenter,
        private readonly TpProgramScheduleSlots $scheduleSlots,
        private readonly TpShiftDriverSupport $shiftSupport,
    ) {}

    public function execute(TpProgramDay $day, Driver $driver, ?string $deviceId = null, ?string $shift = null): TpTripExecution
    {
        return DB::transaction(function () use ($day, $driver, $deviceId, $shift) {
            $day->loadMissing('program');
            $program = $day->program;
            abort_unless($program, 404);

            $multiSlot = count($this->scheduleSlots->slotsForProgram($program)) > 1;
            if ($multiSlot) {
                abort_unless(in_array($shift, ['morning', 'afternoon'], true), 422, 'Chương trình có ca sáng và chiều — cần chỉ rõ ca.');
            }

            $resolvedShift = TpProgramDay::normalizeExecutionShift($shift);

            $existing = $day->executions()->where('shift', $resolvedShift)->first();
            abort_if(
                $existing && $existing->status !== TpTripExecution::STATUS_CANCELLED,
                422,
                'Ca này đã được bắt đầu.',
            );

            abort_if(
                $day->hasInProgressExecutionOtherThan($resolvedShift),
                422,
                'Cần hoàn thành ca đang chạy trước khi bắt đầu ca khác.',
            );

            $effective = $this->driverAssignment->resolveEffective($day, $multiSlot ? $resolvedShift : null);
            abort_unless($effective['driver'] && $effective['driver']->id === $driver->id, 403, 'Bạn không được gán chuyến này.');

            $vehicle = $effective['vehicle'];
            $departureTime = $this->shiftSupport->departureTimeForShift($program, $resolvedShift)
                ?? $program->departure_time;

            $execution = TpTripExecution::query()->create([
                'program_day_id' => $day->id,
                'shift' => $resolvedShift,
                'program_id' => $program->id,
                'driver_id' => $driver->id,
                'vehicle_id' => $vehicle?->id,
                'driver_snapshot' => [
                    'id' => $driver->id,
                    'full_name' => $driver->full_name,
                    'phone' => $driver->phone,
                ],
                'vehicle_snapshot' => $vehicle ? [
                    'id' => $vehicle->id,
                    'plate_number' => $vehicle->plate_number ?? null,
                ] : null,
                'scheduled_time' => $departureTime,
                'started_at' => now(),
                'estimated_cost' => $effective['cost'],
                'status' => TpTripExecution::STATUS_IN_PROGRESS,
                'device_id' => $deviceId,
            ]);

            $enrollments = TpEnrollment::query()
                ->with('student')
                ->where('program_id', $program->id)
                ->whereNull('unenrolled_at')
                ->get();

            $preAbsences = TpDayAbsence::query()
                ->where('program_day_id', $day->id)
                ->pluck('absence_type', 'student_id');

            $expected = 0;
            $absent = 0;

            foreach ($enrollments as $enrollment) {
                $student = $enrollment->student;
                $isPreAbsent = $preAbsences->has($student->id);

                TpTripStudentLog::query()->create([
                    'execution_id' => $execution->id,
                    'student_id' => $student->id,
                    'student_snapshot' => [
                        'id' => $student->id,
                        'code' => $student->code,
                        'full_name' => $student->full_name,
                        'grade' => $student->grade,
                        'class_name' => $student->class_name,
                    ],
                    'initial_status' => $isPreAbsent ? 'pre_absent' : 'expected',
                    'final_status' => $isPreAbsent ? TpTripStudentLog::FINAL_ABSENT : TpTripStudentLog::FINAL_PENDING,
                    'absence_type' => $isPreAbsent ? $preAbsences->get($student->id) : null,
                    'absent_at' => $isPreAbsent ? now() : null,
                ]);

                if ($isPreAbsent) {
                    $absent++;
                } else {
                    $expected++;
                }
            }

            $execution->update([
                'total_expected' => $expected,
                'total_absent' => $absent,
            ]);

            $this->audit->log($driver->user_id, 'execution.started', $execution, $program);

            return $execution->fresh(['studentLogs.student']);
        });
    }
}
