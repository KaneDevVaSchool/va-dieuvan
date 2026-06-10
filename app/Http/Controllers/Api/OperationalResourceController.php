<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Operational\BulkDeleteDriversRequest;
use App\Http\Requests\Api\Operational\BulkDeleteTransportProvidersRequest;
use App\Http\Requests\Api\Operational\BulkDeleteVehiclesRequest;
use App\Http\Requests\Api\Operational\BulkForceDeleteDriversRequest;
use App\Http\Requests\Api\Operational\BulkForceDeleteTransportProvidersRequest;
use App\Http\Requests\Api\Operational\BulkForceDeleteVehiclesRequest;
use App\Http\Requests\Api\Operational\DeleteDriverRequest;
use App\Http\Requests\Api\Operational\DeleteTransportProviderRequest;
use App\Http\Requests\Api\Operational\DeleteVehicleRequest;
use App\Http\Requests\Api\Operational\ForceDeleteDriverRequest;
use App\Http\Requests\Api\Operational\ForceDeleteTransportProviderRequest;
use App\Http\Requests\Api\Operational\ForceDeleteVehicleRequest;
use App\Http\Requests\Api\Operational\ListDriversRequest;
use App\Http\Requests\Api\Operational\ListTransportProvidersRequest;
use App\Http\Requests\Api\Operational\ListVehiclesRequest;
use App\Http\Requests\Api\Operational\RestoreDriverRequest;
use App\Http\Requests\Api\Operational\RestoreTransportProviderRequest;
use App\Http\Requests\Api\Operational\RestoreVehicleRequest;
use App\Http\Requests\Api\Operational\ShowDriverRequest;
use App\Http\Requests\Api\Operational\ShowVehicleRequest;
use App\Http\Requests\Api\Operational\StoreDriverFromUserRequest;
use App\Http\Requests\Api\Operational\StoreDriverRequest;
use App\Http\Requests\Api\Operational\StoreTransportProviderRequest;
use App\Http\Requests\Api\Operational\StoreVehicleRequest;
use App\Http\Requests\Api\Operational\UpdateDriverRequest;
use App\Http\Requests\Api\Operational\UpdateTransportProviderRequest;
use App\Http\Requests\Api\Operational\UpdateVehicleRequest;
use App\Http\Requests\Api\Operational\VehicleConflictsRequest;
use App\Models\Driver;
use App\Models\TransportProvider;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Dispatching\TripScheduleLegService;
use App\Support\TripVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OperationalResourceController extends Controller
{
    use ApiResponses;

    public function vehicles(ListVehiclesRequest $request)
    {
        $data = $request->validated();

        $onlyTrashed = $request->boolean('only_trashed');

        $q = $onlyTrashed
            ? Vehicle::onlyTrashed()
            : Vehicle::query();

        $q->with([
            'defaultDriver.user:id,name,email,phone,employee_code,avatar_url',
        ])
            ->orderBy('license_plate');

        $q->when(isset($data['status']), fn (Builder $b) => $b->where('status', $data['status']));

        $perPage = (int) ($data['per_page'] ?? 100);
        $results = $q->paginate($perPage);

        $items = collect($results->items())->map(fn (Vehicle $v) => $this->serializeVehicle($v))->all();

        return $this->ok([
            'items' => $items,
            'meta' => [
                'current_page' => $results->currentPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
                'last_page' => $results->lastPage(),
            ],
        ]);
    }

    public function showVehicle(ShowVehicleRequest $request, Vehicle $vehicle)
    {
        $vehicle->load(['defaultDriver.user:id,name,email,phone,employee_code,avatar_url']);

        return $this->ok($this->serializeVehicle($vehicle));
    }

    public function vehicleScheduleConflicts(
        VehicleConflictsRequest $request,
        Vehicle $vehicle,
        TripScheduleLegService $scheduleLegs,
    ) {
        $data = $request->validated();
        $tripId = isset($data['trip_id']) ? (int) $data['trip_id'] : null;
        if ($tripId === null || $tripId < 1) {
            return $this->ok(['conflict' => null]);
        }

        $trip = Trip::query()->with(['dispatchRequest.requester:id,name'])->find($tripId);
        if (! $trip instanceof Trip) {
            return $this->ok(['conflict' => null]);
        }

        abort_unless(TripVisibility::userCanViewTrip($request->user(), $trip), 403);

        $excludeId = isset($data['exclude_trip']) ? (int) $data['exclude_trip'] : $tripId;

        $departAt = $trip->depart_at instanceof Carbon ? $trip->depart_at : Carbon::parse($trip->depart_at);
        $arriveBy = $trip->arrive_by
            ? ($trip->arrive_by instanceof Carbon ? $trip->arrive_by : Carbon::parse($trip->arrive_by))
            : $departAt->copy()->addHours(2);

        $dbDriver = DB::getDriverName();
        $plannedEndExpr = $dbDriver === 'mysql'
            ? 'COALESCE(arrive_by, DATE_ADD(depart_at, INTERVAL 2 HOUR))'
            : "COALESCE(arrive_by, datetime(depart_at, '+2 hours'))";

        $candidates = Trip::query()
            ->where('id', '!=', $excludeId)
            ->whereIn('status', ['assigned', 'driver_confirmed', 'in_progress'])
            ->where('depart_at', '<', $arriveBy)
            ->whereRaw("($plannedEndExpr) > ?", [$departAt])
            ->with([
                'dispatchRequest:id,wizard_snapshot,trip_type,requester_id',
                'dispatchRequest.requester:id,name',
            ])
            ->orderBy('depart_at')
            ->get(['id', 'driver_id', 'vehicle_id', 'depart_at', 'arrive_by', 'schedule_assignments', 'dispatch_request_id']);

        $other = null;
        $slotStart = null;
        $slotEnd = null;

        foreach ($candidates as $candidate) {
            foreach ($scheduleLegs->resourceOccupancySlots($candidate) as $slot) {
                if ((int) ($slot['vehicle_id'] ?? 0) !== (int) $vehicle->id) {
                    continue;
                }
                if ($departAt->lt($slot['end']) && $slot['start']->lt($arriveBy)) {
                    $other = $candidate;
                    $slotStart = $slot['start'];
                    $slotEnd = $slot['end'];
                    break 2;
                }
            }
        }

        if (! $other instanceof Trip) {
            return $this->ok(['conflict' => null]);
        }

        $dr = $other->dispatchRequest;
        $requester = $dr?->requester?->name ?? '—';

        return $this->ok([
            'conflict' => [
                'trip_code' => 'TRP-'.str_pad((string) $other->id, 4, '0', STR_PAD_LEFT),
                'time_range' => $slotStart->format('d/m/Y H:i').'–'.$slotEnd->format('H:i'),
                'requester' => $requester,
            ],
        ]);
    }

    public function drivers(ListDriversRequest $request)
    {
        $data = $request->validated();

        $onlyTrashed = $request->boolean('only_trashed');

        $q = $onlyTrashed
            ? Driver::onlyTrashed()
            : Driver::query();

        $q->with(['user:id,name,email,phone,employee_code,avatar_url'])
            ->orderBy('full_name');

        $q->when(
            isset($data['employment_status']),
            fn (Builder $b) => $b->where('employment_status', $data['employment_status']),
        );
        $q->when(
            isset($data['availability_status']),
            fn (Builder $b) => $b->where('availability_status', $data['availability_status']),
        );

        $perPage = (int) ($data['per_page'] ?? 100);
        $results = $q->paginate($perPage);

        $items = collect($results->items())->map(fn (Driver $d) => $this->serializeDriver($d))->all();

        return $this->ok([
            'items' => $items,
            'meta' => [
                'current_page' => $results->currentPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
                'last_page' => $results->lastPage(),
            ],
        ]);
    }

    public function transportProviders(ListTransportProvidersRequest $request)
    {
        $data = $request->validated();

        $onlyTrashed = $request->boolean('only_trashed');

        $q = $onlyTrashed
            ? TransportProvider::onlyTrashed()
            : TransportProvider::query();

        $q->orderBy('name');
        if (array_key_exists('is_active', $data)) {
            $q->where('is_active', (bool) $data['is_active']);
        }

        $perPage = (int) ($data['per_page'] ?? 100);
        $results = $q->paginate($perPage);

        $items = collect($results->items())
            ->map(fn (TransportProvider $p) => $this->serializeTransportProvider($p))
            ->all();

        return $this->ok([
            'items' => $items,
            'meta' => [
                'current_page' => $results->currentPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
                'last_page' => $results->lastPage(),
            ],
        ]);
    }

    public function storeTransportProvider(StoreTransportProviderRequest $request)
    {
        $p = TransportProvider::create($request->validated());

        return $this->created($this->serializeTransportProvider($p));
    }

    public function updateTransportProvider(UpdateTransportProviderRequest $request, TransportProvider $transportProvider)
    {
        $transportProvider->fill($request->validated());
        $transportProvider->save();

        return $this->ok($this->serializeTransportProvider($transportProvider->fresh()));
    }

    public function destroyTransportProvider(DeleteTransportProviderRequest $request, TransportProvider $transportProvider)
    {
        $transportProvider->delete();

        return $this->ok(['deleted' => true, 'trashed' => true]);
    }

    public function restoreTransportProvider(RestoreTransportProviderRequest $request, int $id)
    {
        $provider = TransportProvider::onlyTrashed()->findOrFail($id);
        $provider->restore();

        return $this->ok($this->serializeTransportProvider($provider->fresh()));
    }

    public function forceDeleteTransportProvider(ForceDeleteTransportProviderRequest $request, int $id)
    {
        $provider = TransportProvider::onlyTrashed()->findOrFail($id);
        $provider->forceDelete();

        return $this->ok(['deleted' => true, 'permanent' => true]);
    }

    public function storeVehicle(StoreVehicleRequest $request)
    {
        $vehicle = Vehicle::create($request->validated());
        $vehicle->load(['defaultDriver.user:id,name,email,phone,employee_code,avatar_url']);

        return $this->created($this->serializeVehicle($vehicle));
    }

    public function storeDriver(StoreDriverRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = null;

        $driver = Driver::create($data);
        $driver->load(['user:id,name,email,phone,employee_code,avatar_url']);

        return $this->created($this->serializeDriver($driver));
    }

    public function storeDriverFromUser(StoreDriverFromUserRequest $request)
    {
        $userId = (int) $request->validated('user_id');
        $user = User::findOrFail($userId);

        $driver = Driver::updateOrCreate(
            ['user_id' => $user->id],
            [
                'full_name' => $user->name,
                'phone' => $user->phone,
                'employment_status' => 'active',
                'availability_status' => 'available',
            ]
        );

        $driver->load(['user:id,name,email,phone,employee_code,avatar_url']);

        return $this->created($this->serializeDriver($driver));
    }

    public function showDriver(ShowDriverRequest $request, Driver $driver)
    {
        $driver->load(['user:id,name,email,phone,employee_code,avatar_url']);

        return $this->ok($this->serializeDriver($driver, true));
    }

    public function updateDriver(UpdateDriverRequest $request, Driver $driver)
    {
        $data = $request->validated();

        if (array_key_exists('email', $data)) {
            $email = $data['email'];
            unset($data['email']);
            if ($driver->user_id) {
                $user = $driver->user()->first();
                if ($user) {
                    $user->email = $email;
                    $user->save();
                }
            } else {
                $driver->email = $email;
            }
        }

        $driver->fill($data);
        $driver->save();
        $driver->load(['user:id,name,email,phone,employee_code,avatar_url']);

        return $this->ok($this->serializeDriver($driver, true));
    }

    public function destroyDriver(DeleteDriverRequest $request, Driver $driver)
    {
        $driver->delete();

        return $this->ok(['deleted' => true, 'trashed' => true]);
    }

    public function restoreDriver(RestoreDriverRequest $request, int $id)
    {
        $driver = Driver::onlyTrashed()->findOrFail($id);
        $driver->restore();
        $driver->load(['user:id,name,email,phone,employee_code,avatar_url']);

        return $this->ok($this->serializeDriver($driver));
    }

    public function forceDeleteDriver(ForceDeleteDriverRequest $request, int $id)
    {
        $driver = Driver::onlyTrashed()->findOrFail($id);
        $driver->forceDelete();

        return $this->ok(['deleted' => true, 'permanent' => true]);
    }

    public function updateVehicle(UpdateVehicleRequest $request, Vehicle $vehicle)
    {
        $vehicle->fill($request->validated());
        $vehicle->save();

        $vehicle->load(['defaultDriver.user:id,name,email,phone,employee_code,avatar_url']);

        return $this->ok($this->serializeVehicle($vehicle));
    }

    public function destroyVehicle(DeleteVehicleRequest $request, Vehicle $vehicle)
    {
        $vehicle->delete();

        return $this->ok(['deleted' => true, 'trashed' => true]);
    }

    public function bulkDestroyVehicles(BulkDeleteVehiclesRequest $request)
    {
        $ids = collect($request->validated('ids'))->unique()->values()->all();
        $vehicles = Vehicle::query()->whereIn('id', $ids)->get();
        foreach ($vehicles as $vehicle) {
            $vehicle->delete();
        }

        return $this->ok([
            'deleted_count' => $vehicles->count(),
            'trashed' => true,
        ]);
    }

    public function bulkDestroyDrivers(BulkDeleteDriversRequest $request)
    {
        $ids = collect($request->validated('ids'))->unique()->values()->all();
        $drivers = Driver::query()->whereIn('id', $ids)->get();
        foreach ($drivers as $driver) {
            $driver->delete();
        }

        return $this->ok([
            'deleted_count' => $drivers->count(),
            'trashed' => true,
        ]);
    }

    public function bulkDestroyTransportProviders(BulkDeleteTransportProvidersRequest $request)
    {
        $ids = collect($request->validated('ids'))->unique()->values()->all();
        $providers = TransportProvider::query()->whereIn('id', $ids)->get();
        foreach ($providers as $provider) {
            $provider->delete();
        }

        return $this->ok([
            'deleted_count' => $providers->count(),
            'trashed' => true,
        ]);
    }

    public function bulkForceDeleteVehicles(BulkForceDeleteVehiclesRequest $request)
    {
        $ids = collect($request->validated('ids'))->unique()->values()->all();
        $vehicles = Vehicle::onlyTrashed()->whereIn('id', $ids)->get();
        foreach ($vehicles as $vehicle) {
            $vehicle->forceDelete();
        }

        return $this->ok([
            'deleted_count' => $vehicles->count(),
            'permanent' => true,
        ]);
    }

    public function bulkForceDeleteDrivers(BulkForceDeleteDriversRequest $request)
    {
        $ids = collect($request->validated('ids'))->unique()->values()->all();
        $drivers = Driver::onlyTrashed()->whereIn('id', $ids)->get();
        foreach ($drivers as $driver) {
            $driver->forceDelete();
        }

        return $this->ok([
            'deleted_count' => $drivers->count(),
            'permanent' => true,
        ]);
    }

    public function bulkForceDeleteTransportProviders(BulkForceDeleteTransportProvidersRequest $request)
    {
        $ids = collect($request->validated('ids'))->unique()->values()->all();
        $providers = TransportProvider::onlyTrashed()->whereIn('id', $ids)->get();
        foreach ($providers as $provider) {
            $provider->forceDelete();
        }

        return $this->ok([
            'deleted_count' => $providers->count(),
            'permanent' => true,
        ]);
    }

    public function restoreVehicle(RestoreVehicleRequest $request, int $id)
    {
        $vehicle = Vehicle::onlyTrashed()->findOrFail($id);
        $vehicle->restore();
        $vehicle->load(['defaultDriver.user:id,name,email,phone,employee_code,avatar_url']);

        return $this->ok($this->serializeVehicle($vehicle));
    }

    public function forceDeleteVehicle(ForceDeleteVehicleRequest $request, int $id)
    {
        $vehicle = Vehicle::onlyTrashed()->findOrFail($id);
        $vehicle->forceDelete();

        return $this->ok(['deleted' => true, 'permanent' => true]);
    }

    private function serializeVehicle(Vehicle $v): array
    {
        return [
            'id' => $v->id,
            'license_plate' => $v->license_plate,
            'owner_name' => $v->owner_name,
            'frame_engine_number' => $v->frame_engine_number,
            'type' => $v->type,
            'year_manufactured' => $v->year_manufactured,
            'purchased_at' => $v->purchased_at?->format('Y-m-d'),
            'usage_expires_year' => $v->usage_expires_year,
            'seat_count' => $v->seat_count,
            'payload_kg' => $v->payload_kg,
            'insurance_provider' => $v->insurance_provider,
            'insurance_policy_note' => $v->insurance_policy_note,
            'status' => $v->status,
            'odometer_km' => $v->odometer_km,
            'inspection_expires_at' => $v->inspection_expires_at?->format('Y-m-d'),
            'insurance_expires_at' => $v->insurance_expires_at?->format('Y-m-d'),
            'road_fee_expires_at' => $v->road_fee_expires_at?->format('Y-m-d'),
            'registration_cycle_note' => $v->registration_cycle_note,
            'last_maintenance_at' => $v->last_maintenance_at?->format('Y-m-d'),
            'maintenance_schedule_note' => $v->maintenance_schedule_note,
            'caretaker_name' => $v->caretaker_name,
            'caretaker_phone' => $v->caretaker_phone,
            'notes' => $v->notes,
            'deleted_at' => $v->deleted_at?->toIso8601String(),
            'default_driver' => $v->defaultDriver ? $this->serializeDriver($v->defaultDriver) : null,
        ];
    }

    private function serializeTransportProvider(TransportProvider $p): array
    {
        return [
            'id' => $p->id,
            'name' => $p->name,
            'type' => $p->type,
            'contact_name' => $p->contact_name,
            'contact_phone' => $p->contact_phone,
            'contact_email' => $p->contact_email,
            'notes' => $p->notes,
            'is_active' => $p->is_active,
            'contract_number' => $p->contract_number,
            'contract_signed_at' => $p->contract_signed_at?->format('Y-m-d'),
            'contract_expires_at' => $p->contract_expires_at?->format('Y-m-d'),
            'services' => $p->services ?? [],
            'deleted_at' => $p->deleted_at?->toIso8601String(),
        ];
    }

    private function serializeDriver(Driver $d, bool $detailed = false): array
    {
        $base = [
            'id' => $d->id,
            'full_name' => $d->full_name,
            'phone' => $d->phone,
            'email' => $d->user?->email ?? $d->email,
            'license_class' => $d->license_class,
            'license_expires_at' => $d->license_expires_at?->format('Y-m-d'),
            'employment_status' => $d->employment_status,
            'availability_status' => $d->availability_status,
            'deleted_at' => $d->deleted_at?->toIso8601String(),
            'user' => $d->user ? [
                'id' => $d->user->id,
                'name' => $d->user->name,
                'email' => $d->user->email,
                'phone' => $d->user->phone,
                'employee_code' => $d->user->employee_code,
                'avatar_url' => $d->user->avatar_url,
            ] : null,
        ];
        if ($detailed) {
            $base['national_id'] = $d->national_id;
            $base['odometer_km'] = (int) $d->odometer_km;
        }

        return $base;
    }
}
