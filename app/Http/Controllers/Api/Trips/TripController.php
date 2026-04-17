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

class TripController extends Controller
{
    use ApiResponses;

    public function index(ListTripsRequest $request)
    {
        $data = $request->validated();
        $user = $request->user();

        $q = TripVisibility::visibleTripsQuery($user)
            ->whereHas('dispatchRequest')
            ->with([
                'dispatcher:id,name,email',
                'vehicle:id,license_plate,status',
                'driver:id,full_name',
                'transportProvider:id,name',
                'dispatchRequest:id,status,trip_type,origin,destination',
            ])
            ->orderByDesc('depart_at')
            ->orderByDesc('id');

        $q->when(isset($data['status']), fn (Builder $b) => $b->where('status', $data['status']));
        $q->when(isset($data['from']), fn (Builder $b) => $b->where('depart_at', '>=', Carbon::parse($data['from'])->startOfDay()));
        $q->when(isset($data['to']), fn (Builder $b) => $b->where('depart_at', '<=', Carbon::parse($data['to'])->endOfDay()));

        $q->when(isset($data['trip_type']), fn (Builder $b) => $b->whereHas('dispatchRequest', fn (Builder $dr) => $dr->where('trip_type', $data['trip_type'])));
        $q->when(isset($data['source_channel']), fn (Builder $b) => $b->whereHas('dispatchRequest', fn (Builder $dr) => $dr->where('source_channel', $data['source_channel'])));
        $q->when(isset($data['paper_status']), fn (Builder $b) => $b->whereHas('dispatchRequest', fn (Builder $dr) => $dr->where('paper_status', $data['paper_status'])));
        $q->when(! empty($data['is_urgent']), fn (Builder $b) => $b->whereHas('dispatchRequest', fn (Builder $dr) => $dr->where('is_urgent', true)));

        $q->when(! empty($data['fleet_mode']), function (Builder $b) use ($data) {
            match ($data['fleet_mode']) {
                'internal' => $b->whereNull('transport_provider_id')->whereNotNull('vehicle_id'),
                'vendor_hire' => $b->whereNotNull('transport_provider_id')
                    ->whereHas('transportProvider', function (Builder $p) {
                        $p->where(function (Builder $inner) {
                            $inner->whereNull('type')->orWhere('type', '!=', 'taxi');
                        });
                    }),
                'taxi' => $b->whereNotNull('transport_provider_id')
                    ->whereHas('transportProvider', fn (Builder $p) => $p->where('type', 'taxi')),
                'unspecified' => $b->whereNull('transport_provider_id')->whereNull('vehicle_id'),
                default => null,
            };
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
