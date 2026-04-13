<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Operational\ListDriversRequest;
use App\Http\Requests\Api\Operational\ListTransportProvidersRequest;
use App\Http\Requests\Api\Operational\ListVehiclesRequest;
use App\Http\Requests\Api\Operational\ShowDriverRequest;
use App\Http\Requests\Api\Operational\StoreDriverFromUserRequest;
use App\Http\Requests\Api\Operational\StoreTransportProviderRequest;
use App\Http\Requests\Api\Operational\StoreVehicleRequest;
use App\Http\Requests\Api\Operational\UpdateDriverRequest;
use App\Http\Requests\Api\Operational\UpdateTransportProviderRequest;
use App\Http\Requests\Api\Operational\UpdateVehicleRequest;
use App\Models\Driver;
use App\Models\TransportProvider;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;

class OperationalResourceController extends Controller
{
    use ApiResponses;

    public function vehicles(ListVehiclesRequest $request)
    {
        $data = $request->validated();

        $q = Vehicle::query()
            ->with([
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

    public function drivers(ListDriversRequest $request)
    {
        $data = $request->validated();

        $q = Driver::query()
            ->with(['user:id,name,email,phone,employee_code,avatar_url'])
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

        $q = TransportProvider::query()->orderBy('name');
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

    public function storeVehicle(StoreVehicleRequest $request)
    {
        $vehicle = Vehicle::create($request->validated());
        $vehicle->load(['defaultDriver.user:id,name,email,phone,employee_code,avatar_url']);

        return $this->created($this->serializeVehicle($vehicle));
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
        $driver->fill($request->validated());
        $driver->save();
        $driver->load(['user:id,name,email,phone,employee_code,avatar_url']);

        return $this->ok($this->serializeDriver($driver, true));
    }

    public function updateVehicle(UpdateVehicleRequest $request, Vehicle $vehicle)
    {
        $vehicle->fill($request->validated());
        $vehicle->save();

        $vehicle->load(['defaultDriver.user:id,name,email,phone,employee_code,avatar_url']);

        return $this->ok($this->serializeVehicle($vehicle));
    }

    private function serializeVehicle(Vehicle $v): array
    {
        return [
            'id' => $v->id,
            'license_plate' => $v->license_plate,
            'type' => $v->type,
            'seat_count' => $v->seat_count,
            'payload_kg' => $v->payload_kg,
            'status' => $v->status,
            'odometer_km' => $v->odometer_km,
            'inspection_expires_at' => $v->inspection_expires_at?->format('Y-m-d'),
            'insurance_expires_at' => $v->insurance_expires_at?->format('Y-m-d'),
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
        ];
    }

    private function serializeDriver(Driver $d, bool $detailed = false): array
    {
        $base = [
            'id' => $d->id,
            'full_name' => $d->full_name,
            'phone' => $d->phone,
            'license_class' => $d->license_class,
            'license_expires_at' => $d->license_expires_at?->format('Y-m-d'),
            'employment_status' => $d->employment_status,
            'availability_status' => $d->availability_status,
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
