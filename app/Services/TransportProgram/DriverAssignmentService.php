<?php

namespace App\Services\TransportProgram;

use App\Models\Driver;
use App\Models\TpProgram;
use App\Models\TpProgramDay;
use Carbon\Carbon;

class DriverAssignmentService
{
    public function __construct(
        private readonly TpAuditLogger $audit,
        private readonly TpDriverAssignmentNotifyService $driverNotify,
        private readonly TpShiftDriverSupport $shiftDrivers,
        private readonly int $conflictMinutes = 90,
    ) {}

    /**
     * @return array{driver: ?Driver, vehicle: ?\App\Models\Vehicle, cost: ?string}
     */
    public function resolveEffective(TpProgramDay $day, ?string $shift = null): array
    {
        $day->loadMissing('program');
        $program = $day->program;
        $resolvedShift = $shift;
        if ($program && $this->shiftDrivers->programUsesPerShiftDrivers($program) && $resolvedShift === null) {
            $resolvedShift = 'morning';
        }

        return [
            'driver' => $this->shiftDrivers->effectiveMainDriver($day, $resolvedShift),
            'vehicle' => $day->effectiveVehicle(),
            'cost' => $day->effectiveCost(),
        ];
    }

    public function assignDriver(TpProgramDay $day, int $driverId, ?int $vehicleId, ?int $actorId, ?string $shift = null): void
    {
        $day->loadMissing('program');
        $program = $day->program;
        abort_unless($program, 404);
        abort_unless($program->status === TpProgram::STATUS_ACTIVE, 422, 'Chương trình không còn hoạt động.');
        abort_if($day->day_type === TpProgramDay::DAY_CANCELLED, 422, 'Ngày này đã bị hủy.');
        $resolvedShift = $this->shiftDrivers->normalizeShift($shift, $program);
        $previousMainId = $this->shiftDrivers->effectiveMainDriverId($day, $resolvedShift);
        $this->assertNoConflict($day, $driverId, $resolvedShift);

        $payload = $this->mainAssignPayload($resolvedShift, $driverId, $vehicleId, $actorId);
        $day->update($payload);

        $this->audit->log($actorId, 'day.driver_assigned', $day, $day->program, metadata: [
            'driver_id' => $driverId,
            'vehicle_id' => $vehicleId,
            'shift' => $resolvedShift,
        ]);

        $day->refresh();
        $newMainId = $this->shiftDrivers->effectiveMainDriverId($day, $resolvedShift);
        $this->driverNotify->notifyEffectiveMainChange($day, $previousMainId, $newMainId);
    }

    public function clearOverride(TpProgramDay $day, ?int $actorId, ?string $shift = null): void
    {
        $day->loadMissing('program');
        $program = $day->program;
        abort_unless($program, 404);
        $resolvedShift = $this->shiftDrivers->normalizeShift($shift, $program);
        $previousMainId = $this->shiftDrivers->effectiveMainDriverId($day, $resolvedShift);

        $day->update($this->mainClearPayload($resolvedShift));

        $this->audit->log($actorId, 'day.driver_override_cleared', $day, $day->program, metadata: [
            'shift' => $resolvedShift,
        ]);

        $day->refresh();
        $newMainId = $this->shiftDrivers->effectiveMainDriverId($day, $resolvedShift);
        $this->driverNotify->notifyEffectiveMainChange($day, $previousMainId, $newMainId);
    }

    public function assignBackupDriver(TpProgramDay $day, int $driverId, ?int $actorId, ?string $shift = null): void
    {
        $day->loadMissing('program');
        $program = $day->program;
        abort_unless($program, 404);
        abort_unless($program->status === TpProgram::STATUS_ACTIVE, 422, 'Chương trình không còn hoạt động.');
        abort_if($day->day_type === TpProgramDay::DAY_CANCELLED, 422, 'Ngày này đã bị hủy.');
        $resolvedShift = $this->shiftDrivers->normalizeShift($shift, $program);
        $previousBackupId = $this->shiftDrivers->effectiveBackupDriverId($day, $resolvedShift);

        $day->update($this->backupAssignPayload($resolvedShift, $driverId, $actorId));

        $this->audit->log($actorId, 'day.backup_driver_assigned', $day, $day->program, metadata: [
            'backup_driver_id' => $driverId,
            'shift' => $resolvedShift,
        ]);

        $day->refresh();
        $newBackupId = $this->shiftDrivers->effectiveBackupDriverId($day, $resolvedShift);
        $this->driverNotify->notifyEffectiveBackupChange($day, $previousBackupId, $newBackupId);
    }

    public function clearBackupDriver(TpProgramDay $day, ?int $actorId, ?string $shift = null): void
    {
        $day->loadMissing('program');
        $program = $day->program;
        abort_unless($program, 404);
        $resolvedShift = $this->shiftDrivers->normalizeShift($shift, $program);
        $previousBackupId = $this->shiftDrivers->effectiveBackupDriverId($day, $resolvedShift);

        $day->update($this->backupClearPayload($resolvedShift));

        $this->audit->log($actorId, 'day.backup_driver_override_cleared', $day, $day->program, metadata: [
            'shift' => $resolvedShift,
        ]);

        $day->refresh();
        $newBackupId = $this->shiftDrivers->effectiveBackupDriverId($day, $resolvedShift);
        $this->driverNotify->notifyEffectiveBackupChange($day, $previousBackupId, $newBackupId);
    }

    /**
     * @return array<string, mixed>
     */
    private function mainAssignPayload(?string $shift, int $driverId, ?int $vehicleId, ?int $actorId): array
    {
        if ($shift === 'morning') {
            return ['morning_driver_id' => $driverId];
        }
        if ($shift === 'afternoon') {
            return ['afternoon_driver_id' => $driverId];
        }

        return [
            'driver_id' => $driverId,
            'vehicle_id' => $vehicleId,
            'assigned_at' => now(),
            'assigned_by' => $actorId,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mainClearPayload(?string $shift): array
    {
        if ($shift === 'morning') {
            return ['morning_driver_id' => null];
        }
        if ($shift === 'afternoon') {
            return ['afternoon_driver_id' => null];
        }

        return [
            'driver_id' => null,
            'vehicle_id' => null,
            'assigned_at' => null,
            'assigned_by' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function backupAssignPayload(?string $shift, int $driverId, ?int $actorId): array
    {
        if ($shift === 'morning') {
            return ['morning_backup_driver_id' => $driverId];
        }
        if ($shift === 'afternoon') {
            return ['afternoon_backup_driver_id' => $driverId];
        }

        return [
            'backup_driver_id' => $driverId,
            'backup_assigned_at' => now(),
            'backup_assigned_by' => $actorId,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function backupClearPayload(?string $shift): array
    {
        if ($shift === 'morning') {
            return ['morning_backup_driver_id' => null];
        }
        if ($shift === 'afternoon') {
            return ['afternoon_backup_driver_id' => null];
        }

        return [
            'backup_driver_id' => null,
            'backup_assigned_at' => null,
            'backup_assigned_by' => null,
        ];
    }

    private function assertNoConflict(TpProgramDay $day, int $driverId, ?string $shift): void
    {
        $program = $day->program;
        $time = $shift !== null
            ? ($this->shiftDrivers->departureTimeForShift($program, $shift) ?? $program->departure_time)
            : $program->departure_time;
        if (! $time) {
            return;
        }

        $departure = Carbon::parse($day->scheduled_date->toDateString().' '.$time);

        $others = TpProgramDay::query()
            ->with('program')
            ->driverScheduleVisible()
            ->whereDate('scheduled_date', $day->scheduled_date)
            ->where('id', '!=', $day->id)
            ->get();

        foreach ($others as $other) {
            if (! $this->dayAssignsDriverOnDate($other, $driverId, $shift)) {
                continue;
            }
            $otherProgram = $other->program;
            if (! $otherProgram) {
                continue;
            }
            $otherShift = $shift ?? 'morning';
            $otherTime = $this->shiftDrivers->departureTimeForShift($otherProgram, $otherShift) ?? $otherProgram->departure_time;
            if (! $otherTime) {
                continue;
            }
            $otherDep = Carbon::parse($other->scheduled_date->toDateString().' '.$otherTime);
            if (abs($departure->diffInMinutes($otherDep, false)) < $this->conflictMinutes) {
                abort(409, 'Tài xế đã được gán chuyến khác trong khoảng thời gian trùng.');
            }
        }
    }

    private function dayAssignsDriverOnDate(TpProgramDay $day, int $driverId, ?string $shift): bool
    {
        if ($shift === 'morning') {
            return (int) ($day->morning_driver_id ?? 0) === $driverId
                || ((int) ($day->driver_id ?? 0) === $driverId && $day->morning_driver_id === null);
        }
        if ($shift === 'afternoon') {
            return (int) ($day->afternoon_driver_id ?? 0) === $driverId
                || ((int) ($day->driver_id ?? 0) === $driverId && $day->afternoon_driver_id === null);
        }

        return (int) ($day->driver_id ?? 0) === $driverId;
    }
}
