<?php

namespace App\Http\Controllers\Api\Trips;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Trips\AssignTripRequest;
use App\Http\Requests\Api\Trips\ListTripsRequest;
use App\Http\Requests\Api\Trips\RescheduleTripRequest;
use App\Http\Requests\Api\Trips\ShowTripRequest;
use App\Models\Trip;
use App\Services\Dispatching\DispatchingService;
use App\Support\TripVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TripController extends Controller
{
    use ApiResponses;

    protected function newTripListBuilder($user): Builder
    {
        return TripVisibility::visibleTripsQuery($user)
            ->whereHas('dispatchRequest');
    }

    /**
     * @param  array<string, mixed>  $data  validated list params
     */
    protected function applyTripListFilters(Builder $q, array $data): void
    {
        $q->when(isset($data['status']), fn (Builder $b) => $b->where('trips.status', $data['status']));
        $q->when(isset($data['from']), fn (Builder $b) => $b->where('trips.depart_at', '>=', Carbon::parse($data['from'])->startOfDay()));
        $q->when(isset($data['to']), fn (Builder $b) => $b->where('trips.depart_at', '<=', Carbon::parse($data['to'])->endOfDay()));

        $q->when(isset($data['trip_type']), fn (Builder $b) => $b->whereHas('dispatchRequest', fn (Builder $dr) => $dr->where('trip_type', $data['trip_type'])));
        $q->when(isset($data['source_channel']), fn (Builder $b) => $b->whereHas('dispatchRequest', fn (Builder $dr) => $dr->where('source_channel', $data['source_channel'])));
        $q->when(isset($data['paper_status']), fn (Builder $b) => $b->whereHas('dispatchRequest', fn (Builder $dr) => $dr->where('paper_status', $data['paper_status'])));
        $q->when(! empty($data['is_urgent']), fn (Builder $b) => $b->whereHas('dispatchRequest', fn (Builder $dr) => $dr->where('is_urgent', true)));

        $q->when(! empty($data['fleet_mode']), function (Builder $b) use ($data) {
            match ($data['fleet_mode']) {
                'internal' => $b->whereNull('trips.transport_provider_id')->whereNotNull('trips.vehicle_id'),
                'vendor_hire' => $b->whereNotNull('trips.transport_provider_id')
                    ->whereHas('transportProvider', function (Builder $p) {
                        $p->where(function (Builder $inner) {
                            $inner->whereNull('type')->orWhere('type', '!=', 'taxi');
                        });
                    }),
                'taxi' => $b->whereNotNull('trips.transport_provider_id')
                    ->whereHas('transportProvider', fn (Builder $p) => $p->where('type', 'taxi')),
                'unspecified' => $b->whereNull('trips.transport_provider_id')->whereNull('trips.vehicle_id'),
                default => null,
            };
        });

        $term = isset($data['q']) ? trim((string) $data['q']) : '';
        $q->when($term !== '', function (Builder $b) use ($term) {
            $like = '%'.addcslashes($term, '%_\\').'%';
            $b->where(function (Builder $inner) use ($term, $like) {
                if (ctype_digit($term)) {
                    $inner->where('trips.id', (int) $term);
                }
                $inner->orWhereHas('driver', fn (Builder $d) => $d->where('full_name', 'like', $like))
                    ->orWhereHas('vehicle', fn (Builder $v) => $v->where('license_plate', 'like', $like))
                    ->orWhereHas('dispatchRequest', fn (Builder $dr) => $dr
                        ->where('origin', 'like', $like)
                        ->orWhere('destination', 'like', $like));
            });
        });
    }

    public function index(ListTripsRequest $request)
    {
        $data = $request->validated();
        $user = $request->user();

        $q = $this->newTripListBuilder($user)
            ->with([
                'dispatcher:id,name,email',
                'vehicle:id,license_plate,status',
                'driver:id,full_name,phone',
                'transportProvider:id,name',
                'record:id,trip_id,distance_km',
                'dispatchRequest:id,status,trip_type,origin,destination,arrive_by,passenger_count',
            ])
            ->orderByDesc('trips.depart_at')
            ->orderByDesc('trips.id');

        $this->applyTripListFilters($q, $data);

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
        ]);
    }

    /**
     * Aggregates for list KPIs / tabs (ignores trip_type so breakdown stays stable while a type tab is selected).
     */
    public function stats(ListTripsRequest $request)
    {
        $data = $request->validated();
        $user = $request->user();

        $agg = $data;
        unset($agg['trip_type'], $agg['page'], $agg['per_page']);

        $base = $this->newTripListBuilder($user);
        $this->applyTripListFilters($base, $agg);

        $total = (clone $base)->count();

        $byTypeRaw = (clone $base)
            ->leftJoin('dispatch_requests', 'dispatch_requests.id', '=', 'trips.dispatch_request_id')
            ->select(
                DB::raw('COALESCE(dispatch_requests.trip_type, \'unspecified\') as trip_type'),
                DB::raw('COUNT(*) as c'),
            )
            ->groupBy('trip_type')
            ->pluck('c', 'trip_type');

        $incident = (clone $base)->where('trips.status', 'incident')->count();

        return $this->ok([
            'total' => $total,
            'by_trip_type' => [
                'door_to_door' => (int) ($byTypeRaw['door_to_door'] ?? 0),
                'point_to_point' => (int) ($byTypeRaw['point_to_point'] ?? 0),
                'business' => (int) ($byTypeRaw['business'] ?? 0),
                'cargo' => (int) ($byTypeRaw['cargo'] ?? 0),
                'unspecified' => (int) ($byTypeRaw['unspecified'] ?? 0),
            ],
            'incident' => $incident,
        ]);
    }

    public function show(ShowTripRequest $request, Trip $trip)
    {
        abort_unless(TripVisibility::userCanViewTrip($request->user(), $trip), 403);

        $trip->load([
            'dispatcher:id,name,email,employee_code',
            'vehicle:id,license_plate,status',
            'driver:id,full_name,phone',
            'transportProvider:id,name',
            'dispatchRequest',
            'dispatchRequest.requester:id,name,phone',
            'costs' => fn ($q) => $q->orderByDesc('id')->limit(50),
            'events' => fn ($q) => $q->orderByDesc('id')->limit(50)->with('creator:id,name'),
        ]);

        return $this->ok($trip);
    }

    public function assign(AssignTripRequest $request, Trip $trip, DispatchingService $dispatchingService)
    {
        $data = $request->validated();

        $updated = $dispatchingService->assignResources($trip, [
            ...$data,
            'actor_id' => $request->user()->id,
            'dispatcher_id' => $request->user()->id,
        ]);

        return $this->ok($updated);
    }

    public function reschedule(RescheduleTripRequest $request, Trip $trip, DispatchingService $dispatchingService)
    {
        abort_unless(TripVisibility::userCanViewTrip($request->user(), $trip), 403);

        $updated = $dispatchingService->rescheduleDepartAt($trip, [
            ...$request->validated(),
            'actor_id' => $request->user()->id,
        ]);

        $updated->load([
            'dispatcher:id,name,email',
            'vehicle:id,license_plate,status',
            'driver:id,full_name',
            'transportProvider:id,name',
            'dispatchRequest:id,status,trip_type,origin,destination,arrive_by',
        ]);

        return $this->ok($updated);
    }
}
