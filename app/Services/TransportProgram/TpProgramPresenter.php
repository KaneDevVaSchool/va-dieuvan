<?php

namespace App\Services\TransportProgram;

use App\Models\TpProgram;
use App\Models\TpProgramDay;
use App\Models\TpTripExecution;
use App\Models\TpTripStudentLog;

class TpProgramPresenter
{
    public function programSummary(TpProgram $program): array
    {
        $program->loadCount(['days', 'enrollments' => fn ($q) => $q->whereNull('unenrolled_at')]);

        return [
            'id' => $program->id,
            'code' => $program->code,
            'name' => $program->name,
            'description' => $program->description,
            'status' => $program->status,
            'start_date' => $program->start_date?->toDateString(),
            'end_date' => $program->end_date?->toDateString(),
            'departure_time' => $program->departure_time,
            'return_time' => $program->return_time,
            'origin_name' => $program->origin_name,
            'destination_name' => $program->destination_name,
            'runs_on' => $program->runs_on,
            'default_driver_id' => $program->default_driver_id,
            'default_vehicle_id' => $program->default_vehicle_id,
            'cost_per_trip' => $program->cost_per_trip,
            'day_count' => $program->days_count,
            'enrolled_count' => $program->enrollments_count,
            'responsible_user_id' => $program->responsible_user_id,
            'created_at' => $program->created_at?->toIso8601String(),
        ];
    }

    public function programDay(TpProgramDay $day): array
    {
        $day->loadMissing('program', 'driver', 'vehicle', 'execution');
        $effective = app(DriverAssignmentService::class)->resolveEffective($day);

        return [
            'id' => $day->id,
            'program_id' => $day->program_id,
            'scheduled_date' => $day->scheduled_date->toDateString(),
            'day_type' => $day->day_type,
            'expected_count' => $day->expected_count,
            'driver_id' => $day->driver_id,
            'vehicle_id' => $day->vehicle_id,
            'effective_driver' => $effective['driver'] ? [
                'id' => $effective['driver']->id,
                'full_name' => $effective['driver']->full_name,
            ] : null,
            'effective_vehicle' => $effective['vehicle'] ? [
                'id' => $effective['vehicle']->id,
                'plate_number' => $effective['vehicle']->plate_number ?? null,
            ] : null,
            'has_execution' => $day->execution !== null,
            'execution_status' => $day->execution?->status,
            'notes' => $day->notes,
        ];
    }

    public function execution(TpTripExecution $execution): array
    {
        $execution->loadMissing('programDay', 'studentLogs.student');

        return [
            'id' => $execution->id,
            'program_day_id' => $execution->program_day_id,
            'program_id' => $execution->program_id,
            'status' => $execution->status,
            'started_at' => $execution->started_at?->toIso8601String(),
            'completed_at' => $execution->completed_at?->toIso8601String(),
            'total_expected' => $execution->total_expected,
            'total_boarded' => $execution->total_boarded,
            'total_alighted' => $execution->total_alighted,
            'total_absent' => $execution->total_absent,
            'driver_snapshot' => $execution->driver_snapshot,
            'student_logs' => $execution->studentLogs->map(fn (TpTripStudentLog $log) => [
                'id' => $log->id,
                'student_id' => $log->student_id,
                'full_name' => $log->student_snapshot['full_name'] ?? $log->student?->full_name,
                'code' => $log->student_snapshot['code'] ?? null,
                'initial_status' => $log->initial_status,
                'final_status' => $log->final_status,
                'absence_type' => $log->absence_type,
            ])->values()->all(),
        ];
    }
}
