<?php

namespace App\Services\P2pPolicy;

use App\Models\P2pPolicyTerm;
use App\Models\PolicyRoute;
use App\Models\PolicyStudent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class P2pPolicyRosterResolver
{
    /**
     * @return Collection<int, PolicyStudent>
     */
    public function activeStudentsForRouteOnDate(PolicyRoute $route, Carbon $runDate): Collection
    {
        $dateStr = $runDate->toDateString();
        $term = $route->p2pPolicyTerm;

        return PolicyStudent::query()
            ->where('policy_route_id', $route->id)
            ->where('is_active', true)
            ->where('effective_from', '<=', $dateStr)
            ->where(function ($q) use ($dateStr) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $dateStr);
            })
            ->get()
            ->filter(function (PolicyStudent $ps) use ($runDate, $term) {
                $mask = $ps->active_weekdays_mask ?? $term?->weekdays_mask ?? 31;

                return P2pPolicyWeekdays::isoWeekdayIncludedForDate((int) $mask, $runDate);
            })
            ->values();
    }

    /**
     * Legs to materialize for a route on a date (union of student directions).
     *
     * @return list<'morning'|'afternoon'>
     */
    public function legsForRouteOnDate(PolicyRoute $route, Carbon $runDate): array
    {
        $students = $this->activeStudentsForRouteOnDate($route, $runDate);
        if ($students->isEmpty()) {
            return [];
        }

        $morning = false;
        $afternoon = false;
        foreach ($students as $ps) {
            if ($ps->direction === 'two_way') {
                $morning = true;
                $afternoon = true;
            } else {
                $morning = true;
            }
        }

        $legs = [];
        if ($morning) {
            $legs[] = 'morning';
        }
        if ($afternoon) {
            $legs[] = 'afternoon';
        }

        return $legs;
    }
}
