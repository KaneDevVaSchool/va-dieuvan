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

    /** Chuyến completed / cancelled: không chỉnh danh sách hành khách hoặc chi phí. */
    public static function assertTripAllowsPassengerAndCostEdits(Trip $trip): void
    {
        $s = (string) ($trip->status ?? '');

        if (in_array($s, ['completed', 'cancelled'], true)) {
            abort(409, Messages::TRIP_TERMINAL_NO_LIST_OR_COST_EDITS);
        }
    }

    /**
     * Block new receipts on a cost row once accountant confirmed (BRD: lock after confirm).
     */
    public static function tripCostAllowsDriverEdits(TripCost $cost): bool
    {
        if (! in_array($cost->status, ['draft', 'submitted'], true)) {
            return false;
        }

        $cost->loadMissing('trip');

        if ($cost->trip === null) {
            return true;
        }

        if (self::tripIsPaid($cost->trip)) {
            return false;
        }

        $s = (string) ($cost->trip->status ?? '');

        return ! in_array($s, ['completed', 'cancelled'], true);
    }

    public static function assertTripCostAllowsDriverEdits(TripCost $cost): void
    {
        if (! self::tripCostAllowsDriverEdits($cost)) {
            if ($cost->trip !== null && self::tripIsPaid($cost->trip)) {
                abort(409, Messages::TRIP_FINANCIALLY_LOCKED);
            }
            abort(409, Messages::TRIP_TERMINAL_NO_LIST_OR_COST_EDITS);
        }
    }

    public static function assertTripCostAllowsNewAttachment(TripCost $cost): void
    {
        $cost->loadMissing('trip');

        if ($cost->trip !== null) {
            self::assertTripAllowsPassengerAndCostEdits($cost->trip);

            if (self::tripIsPaid($cost->trip)) {
                abort(409, Messages::TRIP_FINANCIALLY_LOCKED);
            }
        }

        if ($cost->status === 'confirmed') {
            abort(409, Messages::COST_CONFIRMED_NO_ATTACHMENT);
        }

        if (! in_array($cost->status, ['draft', 'submitted'], true)) {
            abort(409, Messages::COST_NOT_ACTIONABLE);
        }
    }
}
