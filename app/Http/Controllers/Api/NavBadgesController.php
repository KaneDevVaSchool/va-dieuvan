<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\CargoShipment;
use App\Models\DispatchRequest;
use Illuminate\Http\Request;

class NavBadgesController extends Controller
{
    use ApiResponses;

    public function __invoke(Request $request)
    {
        $user = $request->user();

        $pendingDispatchRequests = null;
        if ($user->can('viewAny', DispatchRequest::class)) {
            $q = DispatchRequest::query()->where('status', 'pending');
            if (! $user->hasPermission('trip.view_all')) {
                $q->where('requester_id', $user->id);
            }
            $pendingDispatchRequests = $q->count();
        }

        $cargoSlaBreaches = null;
        if ($user->hasPermission('cargo.manage') || $user->hasPermission('trip.assign')) {
            // Same breach semantics as dashboard/report, but scope creation time to the current calendar month
            // so the sidebar stays consistent with the cargo list default “month” preset (which hides older rows).
            $cargoSlaBreaches = CargoShipment::query()
                ->visibleOnStaffCargoIndex()
                ->openSlaBreached()
                ->createdSinceCalendarMonthStart()
                ->count();
        }

        $notificationsUnread = $user->unreadNotifications()->count();

        return $this->ok([
            'pending_dispatch_requests' => $pendingDispatchRequests,
            'cargo_sla_breaches' => $cargoSlaBreaches,
            'notifications_unread' => $notificationsUnread,
        ]);
    }
}
