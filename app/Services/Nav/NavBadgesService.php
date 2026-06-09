<?php

namespace App\Services\Nav;

use App\Models\CargoShipment;
use App\Models\DispatchRequest;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

final class NavBadgesService
{
    /**
     * @return array{
     *     pending_dispatch_requests: int|null,
     *     cargo_sla_breaches: int|null,
     *     notifications_unread: int
     * }
     */
    public function forUser(User $user): array
    {
        return [
            'pending_dispatch_requests' => $this->pendingDispatchRequests($user),
            'cargo_sla_breaches' => $this->cargoSlaBreaches($user),
            'notifications_unread' => $this->notificationsUnread($user),
        ];
    }

    private function pendingDispatchRequests(User $user): ?int
    {
        try {
            if (! $user->can('viewAny', DispatchRequest::class)) {
                return null;
            }

            $q = DispatchRequest::query()->where('status', 'pending');
            if (! $user->hasPermission('trip.view_all')) {
                $q->where('requester_id', $user->id);
            } else {
                $q->visibleOnStaffRequestIndex();
            }

            return $q->count();
        } catch (Throwable $e) {
            Log::warning('nav_badges.pending_dispatch_requests_failed', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function cargoSlaBreaches(User $user): ?int
    {
        try {
            if (! $user->hasPermission('cargo.manage') && ! $user->hasPermission('trip.assign')) {
                return null;
            }

            return CargoShipment::query()
                ->visibleOnStaffCargoIndex()
                ->openSlaBreached()
                ->createdSinceCalendarMonthStart()
                ->count();
        } catch (Throwable $e) {
            Log::warning('nav_badges.cargo_sla_breaches_failed', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function notificationsUnread(User $user): int
    {
        try {
            return $user->unreadNotifications()->count();
        } catch (Throwable $e) {
            Log::warning('nav_badges.notifications_unread_failed', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);

            return 0;
        }
    }
}
