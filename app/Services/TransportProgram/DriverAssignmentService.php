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
    }

    public function clearOverride(TpProgramDay $day, ?int $actorId): void
    {
        $day->update([
            'driver_id' => null,
            'vehicle_id' => null,
            'assigned_at' => null,
            'assigned_by' => null,
        ]);

        $this->audit->log($actorId, 'day.driver_override_cleared', $day, $day->program);
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
