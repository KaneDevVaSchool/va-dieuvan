<?php

namespace App\Http\Controllers\Api\Trips;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Trips\AssignTripRequest;
use App\Http\Requests\Api\Trips\ListTripsRequest;
use App\Http\Requests\Api\Trips\RescheduleTripRequest;
use App\Http\Requests\Api\Trips\ShowTripRequest;
use App\Http\Requests\Api\Trips\UpdateTripPassengerListRequest;
use App\Models\DispatchRequest;
use App\Models\Trip;
use App\Services\Auditing\AuditLogger;
use App\Services\Dispatching\DispatchingService;
use App\Support\FinancialDataLock;
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
        $q->when(isset($data['exclude_trip_type']), function (Builder $b) use ($data) {
            $ex = $data['exclude_trip_type'];
            $b->whereHas('dispatchRequest', function (Builder $dr) use ($ex) {
                $dr->where(function (Builder $w) use ($ex) {
                    $w->whereNull('trip_type')->orWhere('trip_type', '!=', $ex);
                });
            });
        });
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
                'dispatchRequest:id,status,trip_type,origin,destination,arrive_by,passenger_count,source_channel,paper_status,is_urgent,depart_at,notes',
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

        $byStatus = (clone $base)
            ->selectRaw('trips.status, COUNT(*) as c')
            ->groupBy('trips.status')
            ->pluck('c', 'status');

        $awaitingStatuses = ['pending', 'approved', 'assigned', 'driver_confirmed'];
        $awaitingDispatch = 0;
        foreach ($awaitingStatuses as $s) {
            $awaitingDispatch += (int) ($byStatus[$s] ?? 0);
        }

        $inProgress = (int) ($byStatus['in_progress'] ?? 0);
        $completed = (int) ($byStatus['completed'] ?? 0);
        $incident = (int) ($byStatus['incident'] ?? 0);

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
            'by_run' => [
                'awaiting_dispatch' => $awaitingDispatch,
                'in_progress' => $inProgress,
                'completed' => $completed,
                'incident' => $incident,
            ],
        ]);
    }

    public function show(ShowTripRequest $request, Trip $trip)
    {
        abort_unless(TripVisibility::userCanViewTrip($request->user(), $trip), 403);

        $trip->load([
            'dispatcher:id,name,email,employee_code',
            'vehicle:id,license_plate,status,type,seat_count',
            'driver:id,full_name,phone',
            'transportProvider:id,name',
            'record:id,trip_id,distance_km',
            'dispatchRequest',
            'dispatchRequest.requester:id,name,phone,email,employee_code,avatar_url',
            'dispatchRequest.attachments' => fn ($q) => $q->orderByDesc('id')->limit(50),
            'costs' => fn ($q) => $q->orderByDesc('id')->limit(50),
            'events' => fn ($q) => $q->orderByDesc('id')->limit(50)->with('creator:id,name'),
        ]);

        if ($trip->relationLoaded('dispatchRequest') && $trip->dispatchRequest) {
            $trip->dispatchRequest->makeVisible(['wizard_snapshot']);
        }

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

    public function updatePassengerList(UpdateTripPassengerListRequest $request, Trip $trip)
    {
        abort_unless(TripVisibility::userCanViewTrip($request->user(), $trip), 403);

        $trip->refresh();
        FinancialDataLock::assertTripNotPaid($trip);

        $trip->loadMissing('dispatchRequest');
        $dr = $trip->dispatchRequest;
        if (! $dr instanceof DispatchRequest || $dr->trashed()) {
            abort(404, 'Không tìm thấy yêu cầu điều vận.');
        }

        $data = $request->validated();
        $type = (string) $dr->trip_type;

        $passengerIn = $data['passenger_rows'] ?? null;
        $businessIn = $data['business_rows'] ?? null;
        $cargoIn = $data['cargo_rows'] ?? null;

        $nonNull = array_filter([$passengerIn, $businessIn, $cargoIn], fn ($v) => $v !== null);
        if (count($nonNull) !== 1) {
            abort(422, 'Gửi đúng một danh sách (passenger_rows, business_rows hoặc cargo_rows) theo loại chuyến.');
        }

        $beforeSnap = is_array($dr->wizard_snapshot) ? $dr->wizard_snapshot : null;
        $beforePassengerCount = $dr->passenger_count;
        $snap = is_array($beforeSnap) ? $beforeSnap : [];

        if ($type === 'cargo') {
            if ($cargoIn === null) {
                abort(422, 'Loại hàng hóa cần cargo_rows.');
            }
            $normalized = $this->normalizeCargoRows($cargoIn);
            $filled = array_values(array_filter($normalized, fn ($r) => $this->cargoRowFilled($r)));
            if ($filled === []) {
                abort(422, 'Cần ít nhất một dòng hàng hợp lệ.');
            }
            $snap['cargoRows'] = $filled;
            $dr->passenger_count = $this->sumCargoQty($filled);
        } elseif ($type === 'business') {
            if ($businessIn === null) {
                abort(422, 'Loại công tác cần business_rows.');
            }
            $normalized = $this->normalizeBusinessRows($businessIn);
            $filled = array_values(array_filter($normalized, fn ($r) => $this->businessRowFilled($r)));
            if ($filled === []) {
                abort(422, 'Cần ít nhất một dòng đoàn công tác hợp lệ.');
            }
            $snap['businessRows'] = $filled;
            $passengerRows = is_array($snap['passengerRows'] ?? null) ? $snap['passengerRows'] : [];
            $dr->passenger_count = $this->sumGuestsPassengerAndBusiness($passengerRows, $filled);
        } else {
            if ($passengerIn === null) {
                abort(422, 'Loại chuyến này cần passenger_rows.');
            }
            if (! in_array($type, ['door_to_door', 'point_to_point'], true)) {
                abort(422, 'Loại chuyến không hỗ trợ cập nhật danh sách theo cách này.');
            }
            $normalized = $this->normalizePassengerRows($passengerIn);
            $filled = array_values(array_filter($normalized, fn ($r) => $this->passengerRowFilled($r)));
            if ($filled === []) {
                abort(422, 'Cần ít nhất một hành khách hợp lệ.');
            }
            $snap['passengerRows'] = $filled;
            $businessRows = is_array($snap['businessRows'] ?? null) ? $snap['businessRows'] : [];
            $dr->passenger_count = $this->sumGuestsPassengerAndBusiness($filled, $businessRows);
        }

        $dr->wizard_snapshot = $snap;
        $dr->save();

        app(AuditLogger::class)->log(
            actorId: $request->user()->id,
            event: 'dispatch_request.passenger_list_update',
            auditable: $dr,
            before: ['trip_id' => $trip->id, 'wizard_snapshot' => $beforeSnap, 'passenger_count' => $beforePassengerCount],
            after: ['trip_id' => $trip->id, 'wizard_snapshot' => $dr->wizard_snapshot, 'passenger_count' => $dr->passenger_count],
        );

        $trip->load([
            'dispatcher:id,name,email,employee_code',
            'vehicle:id,license_plate,status,type,seat_count',
            'driver:id,full_name,phone',
            'transportProvider:id,name',
            'record:id,trip_id,distance_km',
            'dispatchRequest',
            'dispatchRequest.requester:id,name,phone,email,employee_code,avatar_url',
            'dispatchRequest.attachments' => fn ($q) => $q->orderByDesc('id')->limit(50),
            'costs' => fn ($q) => $q->orderByDesc('id')->limit(50),
            'events' => fn ($q) => $q->orderByDesc('id')->limit(50)->with('creator:id,name'),
        ]);

        if ($trip->relationLoaded('dispatchRequest') && $trip->dispatchRequest) {
            $trip->dispatchRequest->makeVisible(['wizard_snapshot']);
        }

        return $this->ok($trip);
    }

    /**
     * @param  array<int, mixed>  $rows
     * @return array<int, array<string, string>>
     */
    private function normalizePassengerRows(array $rows): array
    {
        $keys = ['depart_at', 'pickup', 'return_at', 'dropoff', 'guests', 'unit_price', 'extra_fee', 'person_in_charge', 'notes'];
        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $item = [];
            foreach ($keys as $k) {
                $v = $row[$k] ?? '';
                $s = is_scalar($v) || $v === null ? trim((string) $v) : '';
                if (mb_strlen($s) > 2000) {
                    $s = mb_substr($s, 0, 2000);
                }
                $item[$k] = $s;
            }
            if ($item['guests'] === '') {
                $item['guests'] = '1';
            }
            $out[] = $item;
        }

        return $out;
    }

    /**
     * @param  array<int, mixed>  $rows
     * @return array<int, array<string, string>>
     */
    private function normalizeBusinessRows(array $rows): array
    {
        $keys = ['depart_at', 'pickup', 'waypoint', 'return_at', 'dropoff', 'guests', 'unit_price', 'extra_fee', 'notes'];
        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $item = [];
            foreach ($keys as $k) {
                $v = $row[$k] ?? '';
                $s = is_scalar($v) || $v === null ? trim((string) $v) : '';
                if (mb_strlen($s) > 2000) {
                    $s = mb_substr($s, 0, 2000);
                }
                $item[$k] = $s;
            }
            if ($item['guests'] === '') {
                $item['guests'] = '1';
            }
            $out[] = $item;
        }

        return $out;
    }

    /**
     * @param  array<int, mixed>  $rows
     * @return array<int, array<string, string>>
     */
    private function normalizeCargoRows(array $rows): array
    {
        $keys = ['name', 'qty', 'dimensions', 'weight', 'item_notes', 'pickup_at', 'pickup_place', 'pickup_contact', 'delivery_at', 'delivery_place', 'delivery_contact', 'transport_note', 'cost'];
        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $item = [];
            foreach ($keys as $k) {
                $v = $row[$k] ?? '';
                $s = is_scalar($v) || $v === null ? trim((string) $v) : '';
                if (mb_strlen($s) > 2000) {
                    $s = mb_substr($s, 0, 2000);
                }
                $item[$k] = $s;
            }
            if ($item['qty'] === '') {
                $item['qty'] = '1';
            }
            $out[] = $item;
        }

        return $out;
    }

    /**
     * @param  array<string, string>  $r
     */
    private function passengerRowFilled(array $r): bool
    {
        if (($r['pickup'] ?? '') !== '' || ($r['dropoff'] ?? '') !== '') {
            return true;
        }
        if (($r['depart_at'] ?? '') !== '' || ($r['return_at'] ?? '') !== '') {
            return true;
        }
        if (($r['person_in_charge'] ?? '') !== '' || ($r['notes'] ?? '') !== '') {
            return true;
        }
        if (($r['unit_price'] ?? '') !== '' || ($r['extra_fee'] ?? '') !== '') {
            return true;
        }
        $g = trim((string) ($r['guests'] ?? ''));

        return $g !== '' && $g !== '1';
    }

    /**
     * @param  array<string, string>  $r
     */
    private function businessRowFilled(array $r): bool
    {
        if (($r['pickup'] ?? '') !== '' || ($r['dropoff'] ?? '') !== '' || ($r['waypoint'] ?? '') !== '') {
            return true;
        }
        if (($r['depart_at'] ?? '') !== '' || ($r['return_at'] ?? '') !== '') {
            return true;
        }
        if (($r['unit_price'] ?? '') !== '' || ($r['extra_fee'] ?? '') !== '') {
            return true;
        }
        if (($r['notes'] ?? '') !== '') {
            return true;
        }
        $g = trim((string) ($r['guests'] ?? ''));

        return $g !== '' && $g !== '1';
    }

    /**
     * @param  array<string, string>  $r
     */
    private function cargoRowFilled(array $r): bool
    {
        if (trim((string) ($r['name'] ?? '')) !== '') {
            return true;
        }
        $qty = trim((string) ($r['qty'] ?? ''));
        if ($qty !== '' && $qty !== '1') {
            return true;
        }

        return (bool) preg_match('/\S/', (string) (($r['pickup_place'] ?? '').($r['delivery_place'] ?? '').($r['pickup_at'] ?? '').($r['delivery_at'] ?? '').($r['pickup_contact'] ?? '').($r['delivery_contact'] ?? '').($r['cost'] ?? '').($r['dimensions'] ?? '').($r['weight'] ?? '').($r['item_notes'] ?? '').($r['transport_note'] ?? '')));
    }

    /**
     * @param  array<int, array<string, string>>  $passengerRows
     * @param  array<int, array<string, string>>  $businessRows
     */
    private function sumGuestsPassengerAndBusiness(array $passengerRows, array $businessRows): int
    {
        $sum = 0;
        foreach ($passengerRows as $r) {
            if (! $this->passengerRowFilled($r)) {
                continue;
            }
            $g = (int) ($r['guests'] ?? 1);

            $sum += $g >= 1 ? $g : 1;
        }
        foreach ($businessRows as $r) {
            if (! $this->businessRowFilled($r)) {
                continue;
            }
            $g = (int) ($r['guests'] ?? 1);
            $sum += $g >= 1 ? $g : 1;
        }

        return max(1, $sum);
    }

    /**
     * @param  array<int, array<string, string>>  $cargoRows
     */
    private function sumCargoQty(array $cargoRows): int
    {
        $sum = 0;
        foreach ($cargoRows as $r) {
            if (! $this->cargoRowFilled($r)) {
                continue;
            }
            $q = (int) ($r['qty'] ?? 1);
            $sum += $q >= 1 ? $q : 1;
        }

        return max(1, $sum);
    }
}
