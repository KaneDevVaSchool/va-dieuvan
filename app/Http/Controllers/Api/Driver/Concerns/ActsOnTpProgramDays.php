<?php

namespace App\Http\Controllers\Api\Driver\Concerns;

use App\Models\Driver;
use App\Models\TpProgramDay;
use App\Models\User;

/**
 * Tiện ích luồng tài xế Transport Program: tài xế đang đăng nhập và
 * quyền thao tác trên ngày vận hành theo effective driver.
 */
trait ActsOnTpProgramDays
{
    protected function actingDriver(?User $user): ?Driver
    {
        if (! $user) {
            return null;
        }

        return Driver::query()->where('user_id', $user->id)->first();
    }

    /** Điều vận (gán tài xế) được mọi ngày; tài xế chỉ ngày mình effective. */
    protected function assertCanActOnProgramDay(?User $user, TpProgramDay $day): void
    {
        if ($user && $user->hasPermission('tp_driver_assign.manage')) {
            return;
        }

        $driver = $this->actingDriver($user);
        $effective = $day->effectiveDriver();
        if ($driver && $effective && (int) $effective->id === (int) $driver->id) {
            return;
        }

        abort(403, 'Bạn không có quyền thao tác trên ngày vận hành này.');
    }
}
