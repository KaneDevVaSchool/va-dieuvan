<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Reports\ReportSummaryRequest;
use App\Models\CargoShipment;
use App\Models\DispatchRequest;
use App\Models\Trip;
use App\Models\TripCost;
use App\Models\Vehicle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    use ApiResponses;

    /**
     * Biểu thức SQL giờ trong ngày (0–23) theo driver — sqlite dùng trong test, mysql thường dùng production.
     */
    protected function hourOfDaySql(string $qualifiedColumn): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "CAST(strftime('%H', {$qualifiedColumn}) AS INTEGER)",
            default => "HOUR({$qualifiedColumn})",
        };
    }

    public function summary(ReportSummaryRequest $request)
    {
        $data = $request->validated();

        $from = isset($data['from']) ? Carbon::parse($data['from'])->startOfDay() : now()->startOfMonth();
        $to = isset($data['to']) ? Carbon::parse($data['to'])->endOfDay() : now()->endOfDay();

        $tripBase = Trip::query()->whereBetween('trips.depart_at', [$from, $to]);
        $costBase = TripCost::query()->whereBetween('created_at', [$from, $to]);

        $tripsByStatus = $tripBase
            ->clone()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $hourSql = $this->hourOfDaySql('trips.depart_at');
        $tripsByHourRaw = $tripBase
            ->clone()
            ->whereNotNull('trips.depart_at')
            ->select(DB::raw("{$hourSql} as hod"), DB::raw('COUNT(*) as total'))
            ->groupBy('hod')
            ->pluck('total', 'hod');
        $tripsByHour = [];
        for ($h = 0; $h < 24; $h++) {
            $tripsByHour[$h] = (int) ($tripsByHourRaw->get($h, 0) ?? 0);
        }

        $tripsByTripType = $tripBase
            ->clone()
            ->leftJoin('dispatch_requests', 'dispatch_requests.id', '=', 'trips.dispatch_request_id')
            ->select(
                DB::raw('COALESCE(dispatch_requests.trip_type, \'unspecified\') as trip_type'),
                DB::raw('COUNT(*) as total'),
            )
            ->groupBy('trip_type')
            ->pluck('total', 'trip_type');

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

        $fleetAgg = DB::table('trips as t')
            ->leftJoin('transport_providers as tp', 'tp.id', '=', 't.transport_provider_id')
            ->whereBetween('t.depart_at', [$from, $to])
            ->selectRaw(
                'SUM(CASE WHEN tp.type = ? THEN 1 ELSE 0 END) as taxi,'.
                'SUM(CASE WHEN t.transport_provider_id IS NOT NULL AND (tp.type IS NULL OR tp.type != ?) THEN 1 ELSE 0 END) as vendor_hire,'.
                'SUM(CASE WHEN t.transport_provider_id IS NULL AND t.vehicle_id IS NOT NULL THEN 1 ELSE 0 END) as internal,'.
                'SUM(CASE WHEN t.transport_provider_id IS NULL AND t.vehicle_id IS NULL THEN 1 ELSE 0 END) as unspecified',
                ['taxi', 'taxi'],
            )
            ->first();

        $tripsByFleetMode = [
            'taxi' => (int) ($fleetAgg->taxi ?? 0),
            'vendor_hire' => (int) ($fleetAgg->vendor_hire ?? 0),
            'internal' => (int) ($fleetAgg->internal ?? 0),
            'unspecified' => (int) ($fleetAgg->unspecified ?? 0),
        ];

        $topRequesters = $tripBase
            ->clone()
            ->join('dispatch_requests', 'dispatch_requests.id', '=', 'trips.dispatch_request_id')
            ->join('users', 'users.id', '=', 'dispatch_requests.requester_id')
            ->select('users.id', 'users.name', 'users.email', DB::raw('COUNT(*) as trip_count'))
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc('trip_count')
            ->limit(15)
            ->get()
            ->map(fn ($r) => [
                'requester_label' => trim((string) ($r->name ?? '')) !== '' ? $r->name : $r->email,
                'trip_count' => (int) $r->trip_count,
            ])
            ->values()
            ->all();

        $costsByPipelineStatus = $costBase
            ->clone()
            ->select('status', DB::raw('SUM(amount) as total_amount'))
            ->groupBy('status')
            ->pluck('total_amount', 'status');

        $dispatchRequestsByStatus = DispatchRequest::query()
            ->whereBetween('depart_at', [$from, $to])
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $tripsByLicensePlate = $tripBase
            ->clone()
            ->whereNotNull('trips.vehicle_id')
            ->join('vehicles', 'vehicles.id', '=', 'trips.vehicle_id')
            ->whereNull('vehicles.deleted_at')
            ->select('vehicles.license_plate', DB::raw('COUNT(*) as c'))
            ->groupBy('vehicles.license_plate')
            ->orderByDesc('c')
            ->limit(12)
            ->pluck('c', 'license_plate');

        $tripRecordsDistanceKm = (float) DB::table('trip_records')
            ->join('trips', 'trips.id', '=', 'trip_records.trip_id')
            ->whereBetween('trips.depart_at', [$from, $to])
            ->whereNotNull('trip_records.distance_km')
            ->sum('trip_records.distance_km');

        $startToday = now()->startOfDay();
        $endComplianceWindow = now()->copy()->addDays(30)->endOfDay();

        $vehicleCompliance = [
            'inspection' => [
                'overdue' => Vehicle::query()->whereNotNull('inspection_expires_at')
                    ->whereDate('inspection_expires_at', '<', $startToday)->count(),
                'due_within_30_days' => Vehicle::query()->whereNotNull('inspection_expires_at')
                    ->whereDate('inspection_expires_at', '>=', $startToday)
                    ->whereDate('inspection_expires_at', '<=', $endComplianceWindow)
                    ->count(),
            ],
            'insurance' => [
                'overdue' => Vehicle::query()->whereNotNull('insurance_expires_at')
                    ->whereDate('insurance_expires_at', '<', $startToday)->count(),
                'due_within_30_days' => Vehicle::query()->whereNotNull('insurance_expires_at')
                    ->whereDate('insurance_expires_at', '>=', $startToday)
                    ->whereDate('insurance_expires_at', '<=', $endComplianceWindow)
                    ->count(),
            ],
            'road_fee' => [
                'overdue' => Vehicle::query()->whereNotNull('road_fee_expires_at')
                    ->whereDate('road_fee_expires_at', '<', $startToday)->count(),
                'due_within_30_days' => Vehicle::query()->whereNotNull('road_fee_expires_at')
                    ->whereDate('road_fee_expires_at', '>=', $startToday)
                    ->whereDate('road_fee_expires_at', '<=', $endComplianceWindow)
                    ->count(),
            ],
            'maintenance' => [
                'no_recent_service_180d' => Vehicle::query()
                    ->where(function ($q) {
                        $q->whereNull('last_maintenance_at')
                            ->orWhereDate('last_maintenance_at', '<', now()->subDays(180));
                    })
                    ->count(),
            ],
        ];

        $completedTrips = (int) ($tripsByStatus['completed'] ?? 0);
        $totalTripsInRange = (int) $tripsByStatus->sum();
        $tripCompletionRatePct = $totalTripsInRange > 0
            ? round(100 * $completedTrips / $totalTripsInRange, 1)
            : null;

        return $this->ok([
            'range' => [
                'from' => $from->toIso8601String(),
                'to' => $to->toIso8601String(),
            ],
            'trips_by_status' => $tripsByStatus,
            'trips_by_hour' => $tripsByHour,
            'trips_by_trip_type' => $tripsByTripType,
            'trips_by_fleet_mode' => $tripsByFleetMode,
            'trips_by_license_plate' => $tripsByLicensePlate,
            'trip_completion' => [
                'completed' => $completedTrips,
                'total' => $totalTripsInRange,
                'rate_pct' => $tripCompletionRatePct,
            ],
            'trip_records_distance_km' => $tripRecordsDistanceKm,
            'top_requesters' => $topRequesters,
            'dispatch_requests_by_status' => $dispatchRequestsByStatus,
            'costs_by_pipeline_status' => $costsByPipelineStatus,
            'confirmed_costs_by_type' => $costsByType,
            'confirmed_costs_by_provider' => $providerSpend,
            'cargo_sla_breaches' => $cargoSlaBreaches,
            'vehicle_compliance' => $vehicleCompliance,
        ]);
    }
}
