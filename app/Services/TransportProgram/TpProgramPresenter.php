<?php

namespace App\Services\TransportProgram;

use App\Models\Driver;
use App\Models\TpProgram;
use App\Models\TpProgramDay;
use App\Models\TpTripExecution;
use App\Models\TpTripStudentLog;
use App\Models\Vehicle;

class TpProgramPresenter
{
    /**
     * @return array{total: int, by_status: array<string, int>, operating_days: int}
     */
    public function listSummary(): array
    {
        $byStatus = TpProgram::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($c) => (int) $c)
            ->all();

        return [
            'total' => (int) array_sum($byStatus),
            'by_status' => $byStatus,
            'operating_days' => (int) TpProgramDay::query()->count(),
        ];
    }

    public function programSummary(TpProgram $program): array
    {
        $program->loadCount(['days', 'enrollments' => fn ($q) => $q->whereNull('unenrolled_at')]);
        $program->loadMissing([
            'responsibleUser:id,name',
            'defaultDriver.user:id,name,avatar_url',
            'backupDriver.user:id,name,avatar_url',
            'defaultVehicle',
        ]);

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
            'backup_driver_id' => $program->backup_driver_id,
            'default_vehicle_id' => $program->default_vehicle_id,
            'default_driver' => $this->driverMini($program->defaultDriver),
            'backup_driver' => $this->driverMini($program->backupDriver),
            'default_vehicle' => $this->vehicleMini($program->defaultVehicle),
            'cost_per_trip' => $program->cost_per_trip,
            'day_count' => $program->days_count,
            'enrolled_count' => $program->enrollments_count,
            'responsible_user_id' => $program->responsible_user_id,
            'responsible_user_name' => $program->responsibleUser?->name,
            'settings' => $program->settings ?? [],
            'created_at' => $program->created_at?->toIso8601String(),
        ];
    }

    public function programDay(TpProgramDay $day): array
    {
        $day->loadMissing('program', 'driver', 'backupDriver', 'vehicle', 'execution');
        $shiftSupport = app(TpShiftDriverSupport::class);
        $program = $day->program;
        $usesPerShift = $program && $shiftSupport->programUsesPerShiftDrivers($program);
        $effective = app(DriverAssignmentService::class)->resolveEffective($day, $usesPerShift ? 'morning' : null);

        return [
            'id' => $day->id,
            'program_id' => $day->program_id,
            'program_name' => $program?->name,
            'program_code' => $program?->code,
            'program_departure_time' => $program?->departure_time,
            'program_return_time' => $program?->return_time,
            'program_origin' => $program?->origin_name,
            'program_destination' => $program?->destination_name,
            'scheduled_date' => $day->scheduled_date->toDateString(),
            'day_type' => $day->day_type,
            'expected_count' => $day->expected_count,
            'driver_id' => $day->driver_id,
            'vehicle_id' => $day->vehicle_id,
            'backup_driver_id' => $day->backup_driver_id,
            'effective_driver' => $this->driverMini($effective['driver']),
            'effective_source' => $day->driver_id ? 'override' : 'default',
            'effective_backup_driver' => $this->driverMini($day->effectiveBackupDriver()),
            'effective_backup_source' => $day->backup_driver_id ? 'override' : 'default',
            'effective_vehicle' => $effective['vehicle'] ? [
                'id' => $effective['vehicle']->id,
                'license_plate' => $effective['vehicle']->license_plate ?? null,
            ] : null,
            'has_execution' => $day->execution !== null,
            'execution_status' => $day->execution?->status,
            'confirmed_at' => $day->confirmed_at?->toIso8601String(),
            'morning_confirmed_at' => $day->morning_confirmed_at?->toIso8601String(),
            'afternoon_confirmed_at' => $day->afternoon_confirmed_at?->toIso8601String(),
            'uses_per_shift_drivers' => $usesPerShift,
            'shift_assignments' => $usesPerShift ? [
                'morning' => $this->shiftAssignmentBlock($day, 'morning', $shiftSupport),
                'afternoon' => $this->shiftAssignmentBlock($day, 'afternoon', $shiftSupport),
            ] : null,
            'notes' => $day->notes,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function shiftAssignmentBlock(TpProgramDay $day, string $shift, TpShiftDriverSupport $shiftSupport): array
    {
        $overrides = $shiftSupport->overrideColumnIds($day, $shift);

        return [
            'driver_id' => $overrides['driver_id'],
            'backup_driver_id' => $overrides['backup_driver_id'],
            'effective_driver' => $this->driverMini($shiftSupport->effectiveMainDriver($day, $shift)),
            'effective_backup_driver' => $this->driverMini($shiftSupport->effectiveBackupDriver($day, $shift)),
            'effective_source' => $overrides['driver_id'] ? 'override' : 'default',
            'effective_backup_source' => $overrides['backup_driver_id'] ? 'override' : 'default',
        ];
    }

    /**
     * Mini driver payload dùng chung cho program & day (default / backup / effective).
     */
    private function driverMini(?Driver $driver): ?array
    {
        if (! $driver) {
            return null;
        }

        return [
            'id' => $driver->id,
            'full_name' => $driver->full_name,
            'phone' => $driver->phone,
            'license_class' => $driver->license_class,
            'availability_status' => $driver->availability_status,
            'employment_status' => $driver->employment_status,
            'avatar_url' => $driver->relationLoaded('user') ? $driver->user?->avatar_url : null,
        ];
    }

    private function vehicleMini(?Vehicle $vehicle): ?array
    {
        if (! $vehicle) {
            return null;
        }

        return [
            'id' => $vehicle->id,
            'license_plate' => $vehicle->license_plate,
            'type' => $vehicle->type,
            'seat_count' => $vehicle->seat_count,
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
                'driver_notes' => $log->driver_notes,
            ])->values()->all(),
        ];
    }
}
