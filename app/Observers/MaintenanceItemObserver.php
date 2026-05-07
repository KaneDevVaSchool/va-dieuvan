<?php

namespace App\Observers;

use App\Models\VehicleMaintenanceItem;
use App\Notifications\MaintenanceExpiryNotification;

class MaintenanceItemObserver
{
    /**
     * After any save, check whether the reminder threshold has been crossed.
     * The driver's linked user receives a push notification when days_remaining
     * first reaches reminder_days_before (± 1 day tolerance).
     */
    public function saved(VehicleMaintenanceItem $item): void
    {
        if (! $item->reminder_enabled || ! $item->reminder_days_before) {
            return;
        }

        $days = $item->days_remaining;
        if ($days === null) {
            return;
        }

        if ($days <= $item->reminder_days_before) {
            $user = optional($item->driver)->user;
            if ($user) {
                $user->notify(new MaintenanceExpiryNotification($item));
            }
        }
    }
}
