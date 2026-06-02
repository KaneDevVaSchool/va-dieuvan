<?php

namespace App\Services\P2pPolicy;

use App\Models\PolicyTripSlot;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PolicyTripSlotIndexQuery
{
    /**
     * @return Builder<PolicyTripSlot>
     */
    public function build(Request $request): Builder
    {
        $q = PolicyTripSlot::query()
            ->with([
                'policyRoute.originCampus',
                'policyRoute.destCampus',
                'policyRoute.driver',
                'policyRoute.vehicle',
                'trip.driver',
                'trip.vehicle',
                'trip.dispatchRequest',
                'p2pPolicyTerm.academicTerm',
                'dispatchRequest',
            ]);

        if ($request->filled('p2p_policy_term_id')) {
            $q->where('p2p_policy_term_id', (int) $request->query('p2p_policy_term_id'));
        }

        if ($request->filled('policy_route_id')) {
            $q->where('policy_route_id', (int) $request->query('policy_route_id'));
        }

        if ($request->filled('run_date_from')) {
            $q->whereDate('run_date', '>=', (string) $request->query('run_date_from'));
        }

        if ($request->filled('run_date_to')) {
            $q->whereDate('run_date', '<=', (string) $request->query('run_date_to'));
        }

        if ($request->filled('leg')) {
            $q->where('leg', (string) $request->query('leg'));
        }

        if ($request->filled('trip_status')) {
            $status = (string) $request->query('trip_status');
            $q->whereHas('trip', fn (Builder $t) => $t->where('status', $status));
        }

        if ($request->filled('has_trip')) {
            if ((string) $request->query('has_trip') === 'yes') {
                $q->whereNotNull('trip_id');
            } elseif ((string) $request->query('has_trip') === 'no') {
                $q->whereNull('trip_id');
            }
        }

        if ($request->filled('reminder_status')) {
            $reminder = (string) $request->query('reminder_status');
            if ($reminder === 'sent') {
                $q->whereNotNull('trip_id')->whereNotNull('depart_reminder_sent_at');
            } elseif ($reminder === 'pending') {
                $q->whereNotNull('trip_id')->whereNull('depart_reminder_sent_at');
            }
        }

        if ($request->filled('q')) {
            $like = '%'.addcslashes(trim((string) $request->query('q')), '%_\\').'%';
            $q->whereHas('policyRoute', function (Builder $r) use ($like) {
                $r->where('name', 'like', $like)
                    ->orWhereHas('originCampus', fn (Builder $c) => $c->where('name', 'like', $like)->orWhere('code', 'like', $like))
                    ->orWhereHas('destCampus', fn (Builder $c) => $c->where('name', 'like', $like)->orWhere('code', 'like', $like));
            });
        }

        return $q->orderByDesc('run_date')->orderBy('leg')->orderByDesc('id');
    }
}
