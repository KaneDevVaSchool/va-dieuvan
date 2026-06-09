<?php

namespace App\Http\Controllers\Api\Driver\Concerns;

use App\Models\Driver;
use App\Models\TpProgramDay;
use App\Models\TpTripExecution;
use App\Models\User;
use App\Services\TransportProgram\TpShiftDriverSupport;

trait ActsOnTpExecutions
{
    protected function actingDriver(?User $user): ?Driver
    {
        if (! $user) {
            return null;
        }

        return Driver::query()->where('user_id', $user->id)->first();
    }

    protected function assertCanStartDay(?User $user, TpProgramDay $day, ?string $shift = null): Driver
    {
        $driver = $this->actingDriver($user);
        abort_unless($driver, 403, 'Tài khoản không phải tài xế.');

        $day->loadMissing('program');
        $support = app(TpShiftDriverSupport::class);
        $effective = null;

        if ($shift !== null) {
            $effective = $support->effectiveMainDriver($day, $shift);
        } elseif ($day->program && $support->programUsesPerShiftDrivers($day->program)) {
            foreach (['morning', 'afternoon'] as $slot) {
                $candidate = $support->effectiveMainDriver($day, $slot);
                if ($candidate && (int) $candidate->id === (int) $driver->id) {
                    $effective = $candidate;
                    break;
                }
            }
        } else {
            $effective = $day->effectiveDriver();
        }

        abort_unless($effective && (int) $effective->id === (int) $driver->id, 403, 'Bạn không được gán chuyến này.');

        return $driver;
    }

    protected function assertCanActOnExecution(?User $user, TpTripExecution $execution): void
    {
        if ($user && ($user->isSuperAdmin() || $user->can('tp_driver_assign.manage'))) {
            return;
        }
        $driver = $this->actingDriver($user);
        abort_unless($driver && (int) $execution->driver_id === (int) $driver->id, 403, 'Không có quyền thao tác chuyến này.');
    }
}
