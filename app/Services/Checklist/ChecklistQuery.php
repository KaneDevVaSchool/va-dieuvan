<?php

namespace App\Services\Checklist;

use App\Models\ChecklistSubmission;
use App\Models\ChecklistTemplate;
use App\Models\Driver;
use App\Models\TpProgramDay;
use App\Models\Trip;
use App\Models\Vehicle;
use App\Support\ChecklistPeriod;
use App\Support\TripScheduleInstant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Truy vấn đọc checklist: lịch sử tài xế, danh sách điều phối, tổng quan kỳ hiện tại, ma trận tuân thủ.
 */
class ChecklistQuery
{
    private const LIST_RELATIONS = [
        'template:id,code,name,frequency',
        'driver:id,full_name,phone',
        'vehicle:id,license_plate,type',
    ];

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, ?Driver $onlyDriver = null): LengthAwarePaginator
    {
        $q = ChecklistSubmission::query()
            ->with(self::LIST_RELATIONS)
            ->withCount([
                'items as items_total',
                'items as items_done' => fn (Builder $b) => $b->whereNotNull('result'),
            ]);

        if ($onlyDriver !== null) {
            $q->where('driver_id', $onlyDriver->id);
        }

        $q->when($filters['template_id'] ?? null, fn (Builder $b, $v) => $b->where('template_id', $v))
            ->when($filters['status'] ?? null, fn (Builder $b, $v) => $b->where('status', $v))
            ->when($filters['result'] ?? null, fn (Builder $b, $v) => $b->where('result', $v))
            ->when($filters['driver_id'] ?? null, fn (Builder $b, $v) => $b->where('driver_id', $v))
            ->when($filters['vehicle_id'] ?? null, fn (Builder $b, $v) => $b->where('vehicle_id', $v))
            ->when($filters['frequency'] ?? null, fn (Builder $b, $v) => $b->where('period_type', $v))
            ->when($filters['date_from'] ?? null, fn (Builder $b, $v) => $b->where('started_at', '>=', Carbon::parse($v, 'Asia/Ho_Chi_Minh')->startOfDay()->utc()))
            ->when($filters['date_to'] ?? null, fn (Builder $b, $v) => $b->where('started_at', '<=', Carbon::parse($v, 'Asia/Ho_Chi_Minh')->endOfDay()->utc()))
            ->when($filters['q'] ?? null, function (Builder $b, $v) {
                $like = '%'.$v.'%';
                $b->where(fn (Builder $w) => $w->where('code', 'like', $like)
                    ->orWhereHas('driver', fn (Builder $d) => $d->where('full_name', 'like', $like))
                    ->orWhereHas('vehicle', fn (Builder $d) => $d->where('license_plate', 'like', $like)));
            });

        return $q->orderByDesc('started_at')->orderByDesc('id')
            ->paginate(min(100, max(1, (int) ($filters['per_page'] ?? 20))));
    }

    /**
     * Kỳ hiện tại của từng template đang bật + phiếu gần nhất + xe có NG hôm nay (banner cảnh báo).
     *
     * @return array<string, mixed>
     */
    public function driverOverview(Driver $driver): array
    {
        $templates = ChecklistTemplate::query()->active()->orderBy('frequency')->orderBy('id')->get();
        $now = Carbon::now();

        $periods = [];
        foreach ($templates as $t) {
            $key = ChecklistPeriod::key($t->frequency, $now);
            $rows = ChecklistSubmission::query()
                ->where('driver_id', $driver->id)
                ->where('template_id', $t->id)
                ->where('period_key', $key)
                ->with('vehicle:id,license_plate')
                ->orderByDesc('id')
                ->get(['id', 'code', 'status', 'result', 'vehicle_id', 'submitted_at', 'started_at', 'critical_ng_count']);

            $submitted = $rows->where('status', '!=', ChecklistSubmission::STATUS_DRAFT);
            $draft = $rows->firstWhere('status', ChecklistSubmission::STATUS_DRAFT);

            $periods[] = [
                'template' => ['id' => $t->id, 'code' => $t->code, 'name' => $t->name, 'frequency' => $t->frequency],
                'period_key' => $key,
                'period_label' => ChecklistPeriod::label($t->frequency, $key),
                'state' => match (true) {
                    $submitted->isNotEmpty() => $submitted->contains('result', ChecklistSubmission::RESULT_FAIL) ? 'fail' : 'pass',
                    $draft !== null => 'draft',
                    default => 'todo',
                },
                'submitted_count' => $submitted->count(),
                'draft_id' => $draft?->id,
                'latest' => $rows->map(fn (ChecklistSubmission $s) => [
                    'id' => $s->id,
                    'code' => $s->code,
                    'status' => $s->status,
                    'result' => $s->result,
                    'license_plate' => $s->vehicle?->license_plate,
                    'submitted_at' => $s->submitted_at?->toIso8601String(),
                ])->take(5)->values()->all(),
            ];
        }

        $todayKey = ChecklistPeriod::key(ChecklistTemplate::FREQUENCY_DAILY, $now);
        $failedVehicles = ChecklistSubmission::query()
            ->where('driver_id', $driver->id)
            ->where('period_key', $todayKey)
            ->where('result', ChecklistSubmission::RESULT_FAIL)
            ->where('status', '!=', ChecklistSubmission::STATUS_DRAFT)
            ->with('vehicle:id,license_plate')
            ->get(['id', 'vehicle_id', 'code', 'critical_ng_count'])
            ->map(fn (ChecklistSubmission $s) => [
                'submission_id' => $s->id,
                'code' => $s->code,
                'vehicle_id' => $s->vehicle_id,
                'license_plate' => $s->vehicle?->license_plate,
                'critical_ng_count' => (int) $s->critical_ng_count,
            ])->values()->all();

        return ['periods' => $periods, 'failed_today' => $failedVehicles];
    }

    /**
     * Dữ liệu cho màn "Làm checklist": template đang bật, xe (xe của tôi lên đầu), chuyến / ngày TP hôm nay để gắn.
     *
     * @return array<string, mixed>
     */
    public function startOptions(Driver $driver): array
    {
        $today = ChecklistPeriod::local();
        $dayStart = $today->copy()->startOfDay()->utc();
        $dayEnd = $today->copy()->endOfDay()->utc();

        $trips = Trip::query()
            ->where('driver_id', $driver->id)
            ->whereBetween('depart_at', [$dayStart, $dayEnd])
            ->whereNotIn('status', ['cancelled'])
            ->with(['dispatchRequest:id,origin,destination', 'vehicle:id,license_plate'])
            ->orderBy('depart_at')
            ->get(['id', 'dispatch_request_id', 'vehicle_id', 'depart_at', 'status']);

        $tpDays = TpProgramDay::query()
            ->whereDate('scheduled_date', $today->toDateString())
            ->where(function (Builder $b) use ($driver) {
                foreach (['driver_id', 'backup_driver_id', 'morning_driver_id', 'morning_backup_driver_id', 'afternoon_driver_id', 'afternoon_backup_driver_id'] as $col) {
                    $b->orWhere($col, $driver->id);
                }
            })
            ->with('program:id,name,default_vehicle_id')
            ->get(['id', 'program_id', 'vehicle_id', 'scheduled_date']);

        $myVehicleIds = collect([$trips->pluck('vehicle_id'), $tpDays->pluck('vehicle_id'), $tpDays->pluck('program.default_vehicle_id')])
            ->flatten()
            ->merge(Vehicle::query()->where('default_driver_id', $driver->id)->pluck('id'))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique();

        $vehicles = Vehicle::query()
            ->orderBy('license_plate')
            ->get(['id', 'license_plate', 'type', 'seat_count', 'status'])
            ->map(fn (Vehicle $v) => [
                'id' => $v->id,
                'license_plate' => $v->license_plate,
                'type' => $v->type,
                'seat_count' => (int) ($v->seat_count ?? 0),
                'status' => $v->status,
                'mine' => $myVehicleIds->contains((int) $v->id),
            ])
            ->sortByDesc('mine')
            ->values()
            ->all();

        return [
            'templates' => ChecklistTemplate::query()->active()->orderBy('frequency')->orderBy('id')
                ->get(['id', 'code', 'name', 'frequency'])->toArray(),
            'vehicles' => $vehicles,
            'trips' => $trips->map(fn (Trip $t) => [
                'id' => $t->id,
                'label' => trim(($t->depart_at?->copy()->setTimezone(TripScheduleInstant::DISPLAY_TZ)->format('H:i') ?? '').' · '
                    .implode(' → ', array_filter([(string) $t->dispatchRequest?->origin, (string) $t->dispatchRequest?->destination]))),
                'vehicle_id' => $t->vehicle_id,
            ])->values()->all(),
            'tp_days' => $tpDays->map(fn (TpProgramDay $d) => [
                'id' => $d->id,
                'label' => (string) ($d->program?->name ?? ('Ngày TP #'.$d->id)),
                'vehicle_id' => $d->vehicle_id ?? $d->program?->default_vehicle_id,
            ])->values()->all(),
        ];
    }

    /**
     * Ma trận tuân thủ: tài xế đang làm việc × kỳ trong khoảng ngày.
     *
     * @return array<string, mixed>
     */
    public function complianceMatrix(ChecklistTemplate $template, Carbon $from, Carbon $to): array
    {
        $keys = ChecklistPeriod::keysBetween($template->frequency, $from, $to);

        $drivers = Driver::query()
            ->where('employment_status', 'active')
            ->whereNotNull('user_id')
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'phone']);

        $subs = ChecklistSubmission::query()
            ->where('template_id', $template->id)
            ->whereIn('period_key', $keys)
            ->whereIn('driver_id', $drivers->pluck('id'))
            ->with('vehicle:id,license_plate')
            ->orderBy('id')
            ->get(['id', 'driver_id', 'vehicle_id', 'period_key', 'status', 'result', 'submitted_at', 'critical_ng_count'])
            ->groupBy(fn (ChecklistSubmission $s) => $s->driver_id.'|'.$s->period_key);

        $totals = ['pass' => 0, 'fail' => 0, 'draft' => 0, 'todo' => 0];
        $rows = $drivers->map(function (Driver $d) use ($keys, $subs, &$totals) {
            $cells = [];
            foreach ($keys as $key) {
                $list = $subs->get($d->id.'|'.$key, collect());
                $done = $list->where('status', '!=', ChecklistSubmission::STATUS_DRAFT);
                $state = match (true) {
                    $done->contains('result', ChecklistSubmission::RESULT_FAIL) => 'fail',
                    $done->isNotEmpty() => 'pass',
                    $list->isNotEmpty() => 'draft',
                    default => 'todo',
                };
                $totals[$state]++;
                $last = $done->last() ?? $list->last();
                $cells[$key] = [
                    'state' => $state,
                    'count' => $done->count(),
                    'submission_id' => $last?->id,
                    'license_plate' => $last?->vehicle?->license_plate,
                    'submitted_at' => $last?->submitted_at?->toIso8601String(),
                ];
            }

            return ['driver' => ['id' => $d->id, 'full_name' => $d->full_name, 'phone' => $d->phone], 'cells' => $cells];
        })->values()->all();

        return [
            'template' => ['id' => $template->id, 'name' => $template->name, 'frequency' => $template->frequency],
            'periods' => array_map(fn ($k) => ['key' => $k, 'label' => ChecklistPeriod::label($template->frequency, $k)], $keys),
            'rows' => $rows,
            'totals' => $totals,
        ];
    }
}
