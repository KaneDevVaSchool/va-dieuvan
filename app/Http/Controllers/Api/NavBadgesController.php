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
            $cargoSlaBreaches = CargoShipment::query()
                ->whereNotIn('status', ['delivered', 'cancelled'])
                ->whereNotNull('sla_due_at')
                ->where('sla_due_at', '<', now())
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
