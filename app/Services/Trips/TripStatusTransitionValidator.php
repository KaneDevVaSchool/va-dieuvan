<?php

namespace App\Services\Trips;

final class TripStatusTransitionValidator
{
    /** @var array<string, list<string>> */
    private const ALLOWED = [
        'pending' => ['cancelled'],
        'approved' => ['assigned', 'driver_confirmed', 'in_progress', 'cancelled'],
        'assigned' => ['driver_confirmed', 'in_progress', 'cancelled', 'incident'],
        'driver_confirmed' => ['in_progress', 'cancelled', 'incident'],
        'in_progress' => ['completed', 'cancelled', 'incident'],
        'incident' => ['in_progress', 'completed', 'cancelled'],
    ];

    public function assertCanTransition(string $fromStatus, string $toStatus): void
    {
        $from = strtolower(trim($fromStatus));
        $to = strtolower(trim($toStatus));

        if ($from === $to) {
            return;
        }

        if (in_array($from, ['completed', 'cancelled'], true)) {
            abort(409, 'Chuyến đã kết thúc; không thể đổi trạng thái.');
        }

        $allowed = self::ALLOWED[$from] ?? [];
        if (! in_array($to, $allowed, true)) {
            abort(422, 'Không thể chuyển trạng thái chuyến từ «'.$from.'» sang «'.$to.'».');
        }
    }
}
