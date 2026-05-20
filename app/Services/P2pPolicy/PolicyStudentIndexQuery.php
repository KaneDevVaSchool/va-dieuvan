<?php

namespace App\Services\P2pPolicy;

use App\Models\PolicyStudent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PolicyStudentIndexQuery
{
    /**
     * @return Builder<PolicyStudent>
     */
    public function build(Request $request): Builder
    {
        $q = PolicyStudent::query()
            ->with(['policyRoute.originCampus', 'policyRoute.destCampus', 'policyRoute.p2pPolicyTerm.academicTerm']);

        if ($request->filled('p2p_policy_term_id')) {
            $termId = (int) $request->query('p2p_policy_term_id');
            $q->whereHas('policyRoute', fn (Builder $r) => $r->where('p2p_policy_term_id', $termId));
        }

        if ($request->filled('policy_route_id')) {
            $q->where('policy_route_id', (int) $request->query('policy_route_id'));
        }

        if ($request->filled('academic_year')) {
            $year = (string) $request->query('academic_year');
            $q->whereHas('policyRoute.p2pPolicyTerm.academicTerm', fn (Builder $t) => $t->where('academic_year', $year));
        }

        if ($request->filled('academic_term_id')) {
            $q->whereHas('policyRoute.p2pPolicyTerm', fn (Builder $t) => $t->where('academic_term_id', (int) $request->query('academic_term_id')));
        }

        if ($request->filled('campus_id')) {
            $campusId = (int) $request->query('campus_id');
            $q->whereHas('policyRoute', function (Builder $r) use ($campusId) {
                $r->where(function (Builder $w) use ($campusId) {
                    $w->where('origin_campus_id', $campusId)->orWhere('dest_campus_id', $campusId);
                });
            });
        }

        if ($request->filled('class_name')) {
            $q->where('class_name', (string) $request->query('class_name'));
        }

        if ($request->query('is_active') !== null && $request->query('is_active') !== '') {
            $q->where('is_active', filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('policy_type')) {
            $q->where('policy_type', (string) $request->query('policy_type'));
        }

        if ($request->filled('weekday_iso')) {
            $iso = max(1, min(7, (int) $request->query('weekday_iso')));
            $bit = 1 << ($iso - 1);
            $q->where(function (Builder $w) use ($bit) {
                $w->whereRaw('(active_weekdays_mask IS NOT NULL AND (active_weekdays_mask & ?) != 0)', [$bit])
                    ->orWhere(function (Builder $w2) use ($bit) {
                        $w2->whereNull('active_weekdays_mask')
                            ->whereHas('policyRoute.p2pPolicyTerm', function (Builder $t) use ($bit) {
                                $t->whereRaw('(weekdays_mask & ?) != 0', [$bit]);
                            });
                    });
            });
        }

        if ($request->filled('q')) {
            $like = '%'.addcslashes(trim((string) $request->query('q')), '%_\\').'%';
            $q->where(function (Builder $w) use ($like) {
                $w->where('student_name', 'like', $like)
                    ->orWhere('student_code', 'like', $like)
                    ->orWhere('class_name', 'like', $like);
            });
        }

        return $q->orderByDesc('id');
    }
}
