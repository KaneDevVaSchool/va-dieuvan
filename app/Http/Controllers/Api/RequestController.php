<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Requests\BulkRestoreDispatchRequestsRequest;
use App\Http\Requests\Api\Requests\BulkSoftDeleteDispatchRequestsRequest;
use App\Http\Requests\Api\Requests\ListRequestsRequest;
use App\Models\DispatchRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class RequestController extends Controller
{
    use ApiResponses;

    public function index(ListRequestsRequest $request)
    {
        $data = $request->validated();
        $user = $request->user();

        $stats = $this->buildStats($user);

        $onlyTrashed = ! empty($data['only_trashed']);

        $q = $this->scopedDispatchRequestsQuery($user);

        if ($onlyTrashed) {
            $q->onlyTrashed();
        }

        $q->with([
            'requester:id,name,email,employee_code',
            'approver:id,name,email,employee_code',
            'trip',
        ])
            ->orderByDesc('depart_at')
            ->orderByDesc('id');

        $q->when(isset($data['q']) && $data['q'] !== '', function (Builder $b) use ($data) {
            $term = trim($data['q']);
            $like = '%'.addcslashes($term, '%_\\').'%';
            $b->where(function (Builder $inner) use ($like, $term) {
                $inner->where('origin', 'like', $like)
                    ->orWhere('destination', 'like', $like)
                    ->orWhere('notes', 'like', $like);
                if (ctype_digit($term)) {
                    $inner->orWhere('id', (int) $term);
                }
            });
        });

        $q->when(isset($data['status']), fn (Builder $b) => $b->where('status', $data['status']));
        $q->when(isset($data['trip_type']), fn (Builder $b) => $b->where('trip_type', $data['trip_type']));
        $q->when(isset($data['source_channel']), fn (Builder $b) => $b->where('source_channel', $data['source_channel']));
        $q->when(isset($data['paper_status']), fn (Builder $b) => $b->where('paper_status', $data['paper_status']));

        $q->when(! empty($data['is_urgent']), fn (Builder $b) => $b->where('is_urgent', true));

        $q->when(isset($data['trip_status']), function (Builder $b) use ($data) {
            $b->whereHas('trip', fn (Builder $t) => $t->where('status', $data['trip_status']));
        });

        $q->when(! empty($data['sla_risk_only']), function (Builder $b) {
            $b->where('status', 'pending')
                ->where(function (Builder $inner) {
                    $inner->where('is_urgent', true)
                        ->orWhere('depart_at', '<=', now()->addHours(48));
                });
        });

        $q->when(isset($data['from']), function (Builder $b) use ($data) {
            $from = Carbon::parse($data['from'])->startOfDay();
            $b->where('depart_at', '>=', $from);
        });
        $q->when(isset($data['to']), function (Builder $b) use ($data) {
            $to = Carbon::parse($data['to'])->endOfDay();
            $b->where('depart_at', '<=', $to);
        });

        $perPage = (int) ($data['per_page'] ?? 20);
        $results = $q->paginate($perPage);

        return $this->ok([
            'items' => $results->items(),
            'meta' => [
                'current_page' => $results->currentPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
                'last_page' => $results->lastPage(),
            ],
            'stats' => $stats,
        ]);
    }

    public function bulkDestroy(BulkSoftDeleteDispatchRequestsRequest $request)
    {
        $user = $request->user();
        $ids = $request->validated()['ids'];
        $deleted = 0;

        foreach ($ids as $id) {
            $dr = DispatchRequest::query()->find($id);
            if (! $dr) {
                continue;
            }
            if (! $user->can('delete', $dr)) {
                continue;
            }
            $dr->delete();
            $deleted++;
        }

        return $this->ok(['deleted' => $deleted]);
    }

    public function bulkRestore(BulkRestoreDispatchRequestsRequest $request)
    {
        $user = $request->user();
        $ids = $request->validated()['ids'];
        $restored = 0;

        foreach ($ids as $id) {
            $dr = DispatchRequest::onlyTrashed()->find($id);
            if (! $dr) {
                continue;
            }
            if (! $user->can('restore', $dr)) {
                continue;
            }
            $dr->restore();
            $restored++;
        }

        return $this->ok(['restored' => $restored]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildStats(User $user): array
    {
        $base = $this->scopedDispatchRequestsQuery($user);

        $statusCounts = (clone $base)
            ->selectRaw('status, count(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status')
            ->all();

        $total = (int) (clone $base)->count();

        $tripsInProgress = (clone $base)
            ->whereHas('trip', fn (Builder $t) => $t->where('status', 'in_progress'))
            ->count();

        $tripsCompleted = (clone $base)
            ->whereHas('trip', fn (Builder $t) => $t->where('status', 'completed'))
            ->count();

        $slaRisk = (clone $base)
            ->where('status', 'pending')
            ->where(function (Builder $inner) {
                $inner->where('is_urgent', true)
                    ->orWhere('depart_at', '<=', now()->addHours(48));
            })
            ->count();

        $startPrev = now()->subMonth()->startOfMonth();
        $endPrev = now()->subMonth()->endOfMonth();
        $startCur = now()->startOfMonth();
        $endCur = now()->endOfMonth();

        $countPrev = (clone $base)->whereBetween('created_at', [$startPrev, $endPrev])->count();
        $countCur = (clone $base)->whereBetween('created_at', [$startCur, $endCur])->count();
        $trendPct = null;
        if ($countPrev > 0) {
            $trendPct = round((($countCur - $countPrev) / $countPrev) * 100, 1);
        }

        $volumeTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i)->startOfDay();
            $volumeTrend[] = (int) (clone $base)
                ->whereBetween('created_at', [$day, $day->copy()->endOfDay()])
                ->count();
        }

        $trashedTotal = (int) (clone $this->scopedDispatchRequestsQuery($user))->onlyTrashed()->count();

        return [
            'total' => $total,
            'by_status' => $statusCounts,
            'trips_in_progress' => (int) $tripsInProgress,
            'trips_completed' => (int) $tripsCompleted,
            'sla_risk' => (int) $slaRisk,
            'month_trend_pct' => $trendPct,
            'volume_trend' => $volumeTrend,
            'trashed_total' => $trashedTotal,
        ];
    }

    private function scopedDispatchRequestsQuery(User $user): Builder
    {
        $q = DispatchRequest::query();

        if (! $user->hasPermission('trip.view_all')) {
            $q->where('requester_id', $user->id);
        }

        return $q;
    }
}
