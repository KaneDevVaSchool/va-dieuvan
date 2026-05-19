<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Requests\BulkForceDeleteDispatchRequestsRequest;
use App\Http\Requests\Api\Requests\BulkRestoreDispatchRequestsRequest;
use App\Http\Requests\Api\Requests\BulkSoftDeleteDispatchRequestsRequest;
use App\Http\Requests\Api\Requests\ListRequestsRequest;
use App\Models\DispatchRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
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
        ]);

        $sort = $data['sort'] ?? 'created_desc';
        match ($sort) {
            'created_asc' => $q->orderBy('created_at')->orderBy('id'),
            'depart_desc' => $q->orderByDesc('depart_at')->orderByDesc('id'),
            'depart_asc' => $q->orderBy('depart_at')->orderBy('id'),
            'id_desc' => $q->orderByDesc('id'),
            default => $q->orderByDesc('created_at')->orderByDesc('id'),
        };

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
                if (preg_match('/^REQ-?(\d+)$/i', $term, $m)) {
                    $inner->orWhere('id', (int) $m[1]);
                }
                $lower = mb_strtolower($term, 'UTF-8');
                if ($lower === 'cargo' || $lower === 'hàng hóa' || $lower === 'hang hoa') {
                    $inner->orWhere('trip_type', 'cargo');
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

        $q->when(! empty($data['recurring_only']), fn (Builder $b) => $b->whereNotNull('dispatch_request_template_id'));

        // Khoảng ngày khởi hành: bản ghi depart_at null (một số luồng cũ / nhập tay) vẫn lọc theo created_at trong khoảng.
        $q->when(isset($data['from']) || isset($data['to']), function (Builder $b) use ($data) {
            $from = isset($data['from']) ? Carbon::parse($data['from'])->startOfDay() : null;
            $to = isset($data['to']) ? Carbon::parse($data['to'])->endOfDay() : null;
            $b->where(function (Builder $outer) use ($from, $to) {
                $outer->where(function (Builder $hasDepart) use ($from, $to) {
                    $hasDepart->whereNotNull('depart_at');
                    if ($from) {
                        $hasDepart->where('depart_at', '>=', $from);
                    }
                    if ($to) {
                        $hasDepart->where('depart_at', '<=', $to);
                    }
                })->orWhere(function (Builder $noDepart) use ($from, $to) {
                    $noDepart->whereNull('depart_at');
                    if ($from) {
                        $noDepart->where('created_at', '>=', $from);
                    }
                    if ($to) {
                        $noDepart->where('created_at', '<=', $to);
                    }
                });
            });
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

    /**
     * Tổng hợp KPI cho màn hình trưởng đơn vị (scoped theo bộ phận).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function deptSummary(Request $request)
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(401);
        }

        if (! $user->isSuperAdmin() && ! $user->hasPermission('request.approve_dept')) {
            abort(403);
        }

        $base = $this->scopedDispatchRequestsQuery($user);

        $pendingCount = (int) (clone $base)->where('status', 'price_filled')->count();

        $startToday = now()->startOfDay();
        $endToday = now()->endOfDay();
        $pendingToday = (int) (clone $base)
            ->where('status', 'price_filled')
            ->whereNotNull('price_filled_at')
            ->whereBetween('price_filled_at', [$startToday, $endToday])
            ->count();

        $startMonth = now()->startOfMonth();
        $endMonth = now()->endOfMonth();
        $approvedThisMonth = (int) (clone $base)
            ->where('status', 'approved')
            ->whereBetween('updated_at', [$startMonth, $endMonth])
            ->count();

        $since30 = now()->copy()->subDays(30)->startOfDay();
        $approved30 = (int) (clone $base)
            ->where('status', 'approved')
            ->where('updated_at', '>=', $since30)
            ->count();
        $rejected30 = (int) (clone $base)
            ->where('status', 'rejected')
            ->where('updated_at', '>=', $since30)
            ->count();
        $decided30 = $approved30 + $rejected30;
        $approvalRate30d = $decided30 > 0 ? (int) round(($approved30 / $decided30) * 100) : null;

        return $this->ok([
            'pending_count' => $pendingCount,
            'pending_today' => $pendingToday,
            'approved_this_month' => $approvedThisMonth,
            'approval_rate_30d' => $approvalRate30d,
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

    public function bulkForceDestroy(BulkForceDeleteDispatchRequestsRequest $request)
    {
        $user = $request->user();
        $ids = $request->validated()['ids'];
        $deleted = 0;

        foreach ($ids as $id) {
            $dr = DispatchRequest::onlyTrashed()->find($id);
            if (! $dr) {
                continue;
            }
            if (! $user->can('forceDelete', $dr)) {
                continue;
            }
            $dr->forceDelete();
            $deleted++;
        }

        return $this->ok(['deleted' => $deleted]);
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

        if ($user->hasPermission('trip.view_all')) {
            return $q;
        }

        if ($user->hasPermission('request.approve_dept')) {
            $deptHeadPure = ! $user->hasPermission('trip.view_all')
                && ! $user->hasPermission('request.approve');

            // Trưởng BP thuần, profile không có phòng: chỉ phiếu gán đích danh.
            if ($deptHeadPure && $user->department_id === null) {
                $q->where('assigned_dept_head_id', (int) $user->id);

                return $q;
            }

            $deptId = $user->department_id !== null ? (int) $user->department_id : null;

            if ($deptHeadPure && $deptId !== null) {
                $uid = (int) $user->id;
                $q->where(function (Builder $outer) use ($uid, $deptId): void {
                    $outer->where('assigned_dept_head_id', $uid)
                        ->orWhere(function (Builder $leg) use ($deptId): void {
                            $leg->whereNull('assigned_dept_head_id')
                                ->whereHas(
                                    'requester',
                                    fn (Builder $b) => $b->where('department_id', $deptId),
                                );
                        });
                });

                return $q;
            }

            if ($deptId !== null) {
                $q->whereHas(
                    'requester',
                    fn (Builder $b) => $b->where('department_id', $deptId),
                );

                return $q;
            }
        }

        $q->where('requester_id', $user->id);

        return $q;
    }
}
