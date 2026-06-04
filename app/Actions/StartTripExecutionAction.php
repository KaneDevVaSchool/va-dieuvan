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
use Illuminate\Support\Facades\DB;

class StartTripExecutionAction
{
    public function __construct(
        private readonly DriverAssignmentService $driverAssignment,
        private readonly TpAuditLogger $audit,
        private readonly TpProgramPresenter $presenter,
    ) {}

    public function execute(TpProgramDay $day, Driver $driver, ?string $deviceId = null): TpTripExecution
    {
        return DB::transaction(function () use ($day, $driver, $deviceId) {
            abort_if($day->execution()->exists(), 422, 'Chuyến đã được bắt đầu.');

            $effective = $this->driverAssignment->resolveEffective($day);
            abort_unless($effective['driver'] && $effective['driver']->id === $driver->id, 403, 'Bạn không được gán chuyến này.');

            $vehicle = $effective['vehicle'];
            $program = $day->program;

            $execution = TpTripExecution::query()->create([
                'program_day_id' => $day->id,
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
                'scheduled_time' => $program->departure_time,
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
