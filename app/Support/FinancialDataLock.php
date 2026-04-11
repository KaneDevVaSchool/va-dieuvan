<?php

namespace App\Support;

use App\Models\Trip;
use App\Models\TripCost;

final class FinancialDataLock
{
    public static function tripIsPaid(Trip $trip): bool
    {
        return ($trip->payment_status ?? 'unpaid') === 'paid';
    }

    public static function assertTripNotPaid(Trip $trip): void
    {
        if (self::tripIsPaid($trip)) {
            abort(409, Messages::TRIP_FINANCIALLY_LOCKED);
        }
    }

    /**
     * Block new receipts on a cost row once accountant confirmed (BRD: lock after confirm).
     */
    public static function assertTripCostAllowsNewAttachment(TripCost $cost): void
    {
        $cost->loadMissing('trip');

        if (self::tripIsPaid($cost->trip)) {
            abort(409, Messages::TRIP_FINANCIALLY_LOCKED);
        }

        if ($cost->status === 'confirmed') {
            abort(409, Messages::COST_CONFIRMED_NO_ATTACHMENT);
        }
    }
}
