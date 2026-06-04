<?php

namespace App\Http\Controllers\Api\Driver\Concerns;

use App\Models\Driver;
use App\Models\PolicyTrip;
use App\Models\User;

/**
 * Tiện ích chung cho các controller luồng tài xế policy: tìm tài xế đang đăng
 * nhập và chặn thao tác lên chuyến không thuộc về tài xế đó (trừ điều vận).
 */
trait ActsOnPolicyTrips
{
    protected function actingDriver(?User $user): ?Driver
    {
        if (! $user) {
            return null;
        }

        return Driver::query()->where('user_id', $user->id)->first();
    }

    /** Điều vận (có quyền gán) thao tác được mọi chuyến; tài xế chỉ chuyến của mình. */
    protected function assertCanActOnTrip(?User $user, PolicyTrip $trip): void
    {
        if ($user && $user->hasPermission('policy_trip.assign_driver')) {
            return;
        }

        $driver = $this->actingDriver($user);
        if ($driver && (int) $trip->driver_id === (int) $driver->id) {
            return;
        }

        abort(403, 'Bạn không có quyền thao tác trên chuyến này.');
    }
}
