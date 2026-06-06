<?php

namespace App\Services\TransportProgram;

use App\Models\Driver;
use App\Models\TpProgram;
use App\Models\TpProgramDay;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DriverAssignmentService
{
    public function __construct(
        private readonly TpAuditLogger $audit,
        private readonly TpDriverAssignmentNotifyService $driverNotify,
        private readonly int $conflictMinutes = 90,
    ) {}

    /**
     * @return array{driver: ?Driver, vehicle: ?\App\Models\Vehicle, cost: ?string}
     */
    public function resolveEffective(TpProgramDay $day): array
    {
        $day->loadMissing('program');

        return [
            'driver' => $day->effectiveDriver(),
            'vehicle' => $day->effectiveVehicle(),
            'cost' => $day->effectiveCost(),
        ];
    }

    public function assignDriver(TpProgramDay $day, int $driverId, ?int $vehicleId, ?int $actorId): void
    {
        $day->loadMissing('program');
        $previousMainId = $day->effectiveDriver()?->id;
        $this->assertNoConflict($day, $driverId);

        $day->update([
            'driver_id' => $driverId,
            'vehicle_id' => $vehicleId,
            'assigned_at' => now(),
            'assigned_by' => $actorId,
        ]);

        $this->audit->log($actorId, 'day.driver_assigned', $day, $day->program, metadata: [
            'driver_id' => $driverId,
            'vehicle_id' => $vehicleId,
        ]);

        $day->refresh();
        $this->driverNotify->notifyEffectiveMainChange($day, $previousMainId, $day->effectiveDriver()?->id);
    }

    public function clearOverride(TpProgramDay $day, ?int $actorId): void
    {
        $day->loadMissing('program');
        $previousMainId = $day->effectiveDriver()?->id;

        $day->update([
            'driver_id' => null,
            'vehicle_id' => null,
            'assigned_at' => null,
            'assigned_by' => null,
        ]);

        $this->audit->log($actorId, 'day.driver_override_cleared', $day, $day->program);

        $day->refresh();
        $this->driverNotify->notifyEffectiveMainChange($day, $previousMainId, $day->effectiveDriver()?->id);
    }

    public function assignBackupDriver(TpProgramDay $day, int $driverId, ?int $actorId): void
    {
        $day->loadMissing('program');
        $previousBackupId = $day->effectiveBackupDriver()?->id;

        $day->update([
            'backup_driver_id' => $driverId,
            'backup_assigned_at' => now(),
            'backup_assigned_by' => $actorId,
        ]);

        $this->audit->log($actorId, 'day.backup_driver_assigned', $day, $day->program, metadata: [
            'backup_driver_id' => $driverId,
        ]);

        $day->refresh();
        $this->driverNotify->notifyEffectiveBackupChange($day, $previousBackupId, $day->effectiveBackupDriver()?->id);
    }

    public function clearBackupDriver(TpProgramDay $day, ?int $actorId): void
    {
        $day->loadMissing('program');
        $previousBackupId = $day->effectiveBackupDriver()?->id;

        $day->update([
            'backup_driver_id' => null,
            'backup_assigned_at' => null,
            'backup_assigned_by' => null,
        ]);

        $this->audit->log($actorId, 'day.backup_driver_override_cleared', $day, $day->program);

        $day->refresh();
        $this->driverNotify->notifyEffectiveBackupChange($day, $previousBackupId, $day->effectiveBackupDriver()?->id);
    }

    private function assertNoConflict(TpProgramDay $day, int $driverId): void
    {
        $program = $day->program;
        $departure = Carbon::parse($day->scheduled_date->toDateString().' '.$program->departure_time);

        $others = TpProgramDay::query()
            ->with('program')
            ->where('driver_id', $driverId)
            ->whereDate('scheduled_date', $day->scheduled_date)
            ->where('id', '!=', $day->id)
            ->where('day_type', TpProgramDay::DAY_OPERATING)
            ->get();

        foreach ($others as $other) {
            $otherDep = Carbon::parse($other->scheduled_date->toDateString().' '.$other->program->departure_time);
            if (abs($departure->diffInMinutes($otherDep, false)) < $this->conflictMinutes) {
                abort(409, 'Tài xế đã được gán chuyến khác trong khoảng thời gian trùng.');
            }
        }
    }
}
