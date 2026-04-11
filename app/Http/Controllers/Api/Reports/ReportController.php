<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Reports\ReportSummaryRequest;
use App\Models\CargoShipment;
use App\Models\Trip;
use App\Models\TripCost;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    use ApiResponses;

    public function summary(ReportSummaryRequest $request)
    {
        $data = $request->validated();

        $from = isset($data['from']) ? Carbon::parse($data['from'])->startOfDay() : now()->startOfMonth();
        $to = isset($data['to']) ? Carbon::parse($data['to'])->endOfDay() : now()->endOfDay();

        $tripBase = Trip::query()->whereBetween('depart_at', [$from, $to]);
        $costBase = TripCost::query()->whereBetween('created_at', [$from, $to]);

        $tripsByStatus = $tripBase
            ->clone()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $costsByType = $costBase
            ->clone()
            ->where('status', 'confirmed')
            ->select('type', DB::raw('SUM(amount) as total_amount'))
            ->groupBy('type')
            ->pluck('total_amount', 'type');

        $providerSpend = TripCost::query()
            ->join('trips', 'trips.id', '=', 'trip_costs.trip_id')
            ->leftJoin('transport_providers', 'transport_providers.id', '=', 'trips.transport_provider_id')
            ->where('trip_costs.status', 'confirmed')
            ->whereBetween('trip_costs.created_at', [$from, $to])
            ->selectRaw("COALESCE(transport_providers.name, 'INTERNAL') as provider, SUM(trip_costs.amount) as total_amount")
            ->groupByRaw("COALESCE(transport_providers.name, 'INTERNAL')")
            ->orderByDesc('total_amount')
            ->limit(50)
            ->get();

        $cargoSlaBreaches = CargoShipment::query()
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->whereNotNull('sla_due_at')
            ->where('sla_due_at', '<', now())
            ->count();

        return $this->ok([
            'range' => [
                'from' => $from->toIso8601String(),
                'to' => $to->toIso8601String(),
            ],
            'trips_by_status' => $tripsByStatus,
            'confirmed_costs_by_type' => $costsByType,
            'confirmed_costs_by_provider' => $providerSpend,
            'cargo_sla_breaches' => $cargoSlaBreaches,
        ]);
    }
}
