<?php

namespace App\Http\Controllers\Api\Driver\Concerns;

use App\Models\Driver;
use App\Models\TpProgramDay;
use App\Models\TpTripExecution;
use App\Models\User;

trait ActsOnTpExecutions
{
    protected function actingDriver(?User $user): ?Driver
    {
        if (! $user) {
            return null;
        }

        return Driver::query()->where('user_id', $user->id)->first();
    }

    protected function assertCanStartDay(?User $user, TpProgramDay $day): Driver
    {
        $driver = $this->actingDriver($user);
        abort_unless($driver, 403, 'Tài khoản không phải tài xế.');

        $effective = $day->loadMissing('program')->effectiveDriver();
        abort_unless($effective && $effective->id === $driver->id, 403, 'Bạn không được gán chuyến này.');

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
