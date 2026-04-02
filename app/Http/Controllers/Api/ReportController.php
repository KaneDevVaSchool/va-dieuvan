<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CargoShipment;
use App\Models\Trip;
use App\Models\TripCost;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function summary(Request $request)
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

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
            ->where('status', 'confirmed')
            ->whereBetween('created_at', [$from, $to])
            ->join('trips', 'trips.id', '=', 'trip_costs.trip_id')
            ->leftJoin('transport_providers', 'transport_providers.id', '=', 'trips.transport_provider_id')
            ->selectRaw('COALESCE(transport_providers.name, "INTERNAL") as provider, SUM(trip_costs.amount) as total_amount')
            ->groupBy('provider')
            ->orderByDesc('total_amount')
            ->limit(50)
            ->get();

        $cargoSlaBreaches = CargoShipment::query()
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->whereNotNull('sla_due_at')
            ->where('sla_due_at', '<', now())
            ->count();

        return response()->json([
            'data' => [
                'range' => [
                    'from' => $from->toIso8601String(),
                    'to' => $to->toIso8601String(),
                ],
                'trips_by_status' => $tripsByStatus,
                'confirmed_costs_by_type' => $costsByType,
                'confirmed_costs_by_provider' => $providerSpend,
                'cargo_sla_breaches' => $cargoSlaBreaches,
            ],
        ]);
    }
}

