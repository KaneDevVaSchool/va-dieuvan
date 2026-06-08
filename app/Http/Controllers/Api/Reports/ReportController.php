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
use Illuminate\Database\Eloquent\Builder;
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

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function applyTripSummaryFilters(Builder $q, array $filters): void
    {
        // Dispatch requests use soft deletes ("trash"); exclude their trips from aggregates.
        $q->whereHas('dispatchRequest');

        if (! empty($filters['trip_status'])) {
            $q->where('trips.status', $filters['trip_status']);
        }

        $fleet = $filters['fleet_mode'] ?? null;
        if ($fleet === 'internal') {
            $q->whereNull('trips.transport_provider_id')->whereNotNull('trips.vehicle_id');
        } elseif ($fleet === 'vendor_hire') {
            $q->whereNotNull('trips.transport_provider_id')
                ->whereHas('transportProvider', function (Builder $p) {
                    $p->where(function (Builder $inner) {
                        $inner->whereNull('type')->orWhere('type', '!=', 'taxi');
                    });
                });
        } elseif ($fleet === 'taxi') {
            $q->whereNotNull('trips.transport_provider_id')
                ->whereHas('transportProvider', fn (Builder $p) => $p->where('type', 'taxi'));
        } elseif ($fleet === 'unspecified') {
            $q->whereNull('trips.transport_provider_id')->whereNull('trips.vehicle_id');
        }

        if (! empty($filters['trip_type'])) {
            $q->whereHas('dispatchRequest', fn (Builder $dr) => $dr->where('trip_type', $filters['trip_type']));
        }
        if (! empty($filters['source_channel'])) {
            $q->whereHas('dispatchRequest', fn (Builder $dr) => $dr->where('source_channel', $filters['source_channel']));
        }
        if (! empty($filters['paper_status'])) {
            $q->whereHas('dispatchRequest', fn (Builder $dr) => $dr->where('paper_status', $filters['paper_status']));
        }
        if (! empty($filters['is_urgent'])) {
            $q->whereHas('dispatchRequest', fn (Builder $dr) => $dr->where('is_urgent', true));
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function applyDispatchSummaryFilters(Builder $q, array $filters): void
    {
        if (! empty($filters['trip_type'])) {
            $q->where('trip_type', $filters['trip_type']);
        }
        if (! empty($filters['source_channel'])) {
            $q->where('source_channel', $filters['source_channel']);
        }
        if (! empty($filters['paper_status'])) {
            $q->where('paper_status', $filters['paper_status']);
        }
        if (! empty($filters['is_urgent'])) {
            $q->where('is_urgent', true);
        }
    }

    public function summary(ReportSummaryRequest $request)
    {
        $data = $request->validated();

        $fromRaw = $data['from'] ?? null;
        $toRaw = $data['to'] ?? null;
        $hasFrom = $fromRaw !== null && $fromRaw !== '';
        $hasTo = $toRaw !== null && $toRaw !== '';
        $dateBounded = $hasFrom || $hasTo;

        if ($dateBounded) {
            $from = $hasFrom ? Carbon::parse($fromRaw)->startOfDay() : now()->startOfMonth();
            $to = $hasTo ? Carbon::parse($toRaw)->endOfDay() : now()->endOfDay();
        } else {
            $from = null;
            $to = null;
        }

        $tripBase = Trip::query();
        if ($dateBounded) {
            $tripBase->whereBetween('trips.depart_at', [$from, $to]);
        }
        $this->applyTripSummaryFilters($tripBase, $data);

        $costBase = TripCost::query()
            ->whereHas('trip', function (Builder $t) use ($data) {
                $this->applyTripSummaryFilters($t, $data);
            });
        if ($dateBounded) {
            $costBase->whereBetween('trip_costs.created_at', [$from, $to]);
        }

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
            ->groupByRaw($hourSql)
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
            ->where('trip_costs.status', 'confirmed')
            ->select('trip_costs.type', DB::raw('SUM(trip_costs.amount) as total_amount'))
            ->groupBy('trip_costs.type')
            ->pluck('total_amount', 'type');

        $providerSpend = TripCost::query()
            ->join('trips', 'trips.id', '=', 'trip_costs.trip_id')
            ->leftJoin('transport_providers', 'transport_providers.id', '=', 'trips.transport_provider_id')
            ->where('trip_costs.status', 'confirmed')
            ->when($dateBounded, fn (Builder $q) => $q->whereBetween('trip_costs.created_at', [$from, $to]))
            ->whereHas('trip', function (Builder $t) use ($data) {
                $this->applyTripSummaryFilters($t, $data);
            })
            ->selectRaw("COALESCE(transport_providers.name, 'INTERNAL') as provider, SUM(trip_costs.amount) as total_amount")
            ->groupByRaw("COALESCE(transport_providers.name, 'INTERNAL')")
            ->orderByDesc('total_amount')
            ->limit(50)
            ->get();

        $cargoSlaQuery = CargoShipment::query()
            ->visibleOnStaffCargoIndex()
            ->openSlaBreached();
        if ($dateBounded) {
            $cargoSlaQuery->whereBetween('cargo_shipments.created_at', [
                $from->copy()->startOfDay(),
                $to->copy()->endOfDay(),
            ]);
        }
        $cargoSlaBreaches = $cargoSlaQuery->count();

        $fleetAgg = $tripBase
            ->clone()
            ->leftJoin('transport_providers as tp', 'tp.id', '=', 'trips.transport_provider_id')
            ->selectRaw(
                'SUM(CASE WHEN tp.type = ? THEN 1 ELSE 0 END) as taxi,'.
                'SUM(CASE WHEN trips.transport_provider_id IS NOT NULL AND (tp.type IS NULL OR tp.type != ?) THEN 1 ELSE 0 END) as vendor_hire,'.
                'SUM(CASE WHEN trips.transport_provider_id IS NULL AND trips.vehicle_id IS NOT NULL THEN 1 ELSE 0 END) as internal,'.
                'SUM(CASE WHEN trips.transport_provider_id IS NULL AND trips.vehicle_id IS NULL THEN 1 ELSE 0 END) as unspecified',
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
            ->select('trip_costs.status', DB::raw('SUM(trip_costs.amount) as total_amount'))
            ->groupBy('trip_costs.status')
            ->pluck('total_amount', 'status');

        $dispatchBase = DispatchRequest::query();
        if ($dateBounded) {
            $dispatchBase->whereBetween('depart_at', [$from, $to]);
        }
        $this->applyDispatchSummaryFilters($dispatchBase, $data);

        $dispatchRequestsByStatus = $dispatchBase
            ->clone()
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

        $tripIdSub = $tripBase->clone()->select('trips.id');
        $tripRecordsDistanceKm = (float) DB::table('trip_records')
            ->whereIn('trip_id', $tripIdSub)
            ->whereNotNull('distance_km')
            ->sum('distance_km');

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
            'range' => $dateBounded
                ? [
                    'from' => $from->toIso8601String(),
                    'to' => $to->toIso8601String(),
                ]
                : [
                    'from' => null,
                    'to' => null,
                    'all_time' => true,
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
