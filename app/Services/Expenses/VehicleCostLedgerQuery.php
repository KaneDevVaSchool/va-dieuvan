<?php

namespace App\Services\Expenses;

use App\Models\TripCost;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Sổ chi phí theo xe (định danh từng xe): trip_costs gắn trực tiếp vehicle_id hoặc qua chuyến.
 * Ngày ghi nhận = reported_on, không có thì ngày tạo.
 */
class VehicleCostLedgerQuery
{
    public function __construct(private readonly ExpenseQuery $expenses) {}

    /**
     * Tổng hợp mọi xe trong khoảng ngày.
     *
     * @return array<string, mixed>
     */
    public function summary(string $from, string $to): array
    {
        $rows = $this->baseQuery($from, $to)
            ->selectRaw('COALESCE(trip_costs.vehicle_id, trips.vehicle_id) as vid')
            ->selectRaw('COUNT(*) as cnt')
            ->selectRaw('SUM(trip_costs.amount) as total')
            ->selectRaw("SUM(CASE WHEN trip_costs.status = 'confirmed' THEN trip_costs.amount ELSE 0 END) as confirmed_total")
            ->selectRaw('SUM(CASE WHEN trip_costs.has_invoice = 0 THEN trip_costs.amount ELSE 0 END) as no_invoice_total')
            ->groupBy('vid')
            ->get();

        $vehicles = Vehicle::withTrashed()->whereIn('id', $rows->pluck('vid')->filter())->get(['id', 'license_plate', 'type'])->keyBy('id');

        $items = $rows->filter(fn ($r) => $r->vid !== null)->map(fn ($r) => [
            'vehicle_id' => (int) $r->vid,
            'license_plate' => $vehicles->get((int) $r->vid)?->license_plate,
            'type' => $vehicles->get((int) $r->vid)?->type,
            'count' => (int) $r->cnt,
            'total' => (float) $r->total,
            'confirmed_total' => (float) $r->confirmed_total,
            'no_invoice_total' => (float) $r->no_invoice_total,
        ])->sortByDesc('total')->values();

        return [
            'items' => $items->all(),
            'grand_total' => (float) $items->sum('total'),
        ];
    }

    /**
     * Chi tiết một xe: dòng chi phí + tổng theo loại / tháng + hồ sơ thanh toán đang giữ.
     *
     * @return array<string, mixed>
     */
    public function forVehicle(Vehicle $vehicle, string $from, string $to): array
    {
        $costs = TripCost::query()
            ->from('trip_costs')
            ->leftJoin('trips', 'trips.id', '=', 'trip_costs.trip_id')
            ->where(fn (Builder $w) => $w->where('trip_costs.vehicle_id', $vehicle->id)
                ->orWhere(fn (Builder $x) => $x->whereNull('trip_costs.vehicle_id')->where('trips.vehicle_id', $vehicle->id)))
            ->where('trip_costs.status', '!=', 'rejected')
            ->whereRaw($this->dateExpr().' BETWEEN ? AND ?', [$from, $to])
            ->with('creator:id,name')
            ->select('trip_costs.*')
            ->selectRaw($this->dateExpr().' as ledger_date')
            ->orderByRaw($this->dateExpr().' desc')
            ->orderByDesc('trip_costs.id')
            ->get();

        $holders = $this->expenses->holdersForCosts($costs->pluck('id')->map(fn ($id) => (int) $id)->all());

        $rows = $costs->map(fn (TripCost $c) => [
            'id' => $c->id,
            'date' => (string) $c->ledger_date,
            'type' => $c->type,
            'amount' => (float) $c->amount,
            'description' => $c->description,
            'status' => $c->status,
            'trip_id' => $c->trip_id,
            'has_invoice' => (bool) ($c->has_invoice ?? true),
            'seller_name' => $c->seller_name,
            'created_by' => $c->creator?->name,
            'expense_document' => $holders[(int) $c->id] ?? null,
        ]);

        return [
            'vehicle' => $vehicle->only(['id', 'license_plate', 'type', 'seat_count', 'status']),
            'rows' => $rows->values()->all(),
            'by_type' => $this->groupTotals($rows, 'type'),
            'by_month' => $this->groupTotals($rows->map(fn ($r) => [...$r, 'month' => substr($r['date'], 0, 7)]), 'month'),
            'total' => (float) $rows->sum('amount'),
        ];
    }

    private function baseQuery(string $from, string $to): \Illuminate\Database\Query\Builder
    {
        return DB::table('trip_costs')
            ->leftJoin('trips', 'trips.id', '=', 'trip_costs.trip_id')
            ->where('trip_costs.status', '!=', 'rejected')
            ->whereRaw($this->dateExpr().' BETWEEN ? AND ?', [$from, $to]);
    }

    private function dateExpr(): string
    {
        return 'COALESCE(trip_costs.reported_on, DATE(trip_costs.created_at))';
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return list<array{key: string, count: int, total: float}>
     */
    private function groupTotals(Collection $rows, string $key): array
    {
        return $rows->groupBy($key)
            ->map(fn (Collection $g, $k) => ['key' => (string) $k, 'count' => $g->count(), 'total' => (float) $g->sum('amount')])
            ->sortByDesc('total')
            ->values()
            ->all();
    }
}
