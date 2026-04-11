<?php

namespace App\Http\Controllers\Api\Costs;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Costs\DecideTripCostRequest;
use App\Http\Requests\Api\Costs\ListAllTripCostsRequest;
use App\Http\Requests\Api\Costs\ListTripCostsRequest;
use App\Http\Requests\Api\Costs\OverrideTripCostRequest;
use App\Http\Requests\Api\Costs\SubmitTripCostRequest;
use App\Models\Trip;
use App\Models\TripCost;
use App\Services\Auditing\AuditLogger;
use App\Support\FinancialDataLock;
use App\Support\Messages;
use App\Support\TripVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TripCostController extends Controller
{
    use ApiResponses;

    public function index(ListAllTripCostsRequest $request)
    {
        $data = $request->validated();
        $user = $request->user();

        $q = TripCost::query()
            ->with([
                'trip:id,status,depart_at,dispatcher_id,driver_id',
                'creator:id,name,email',
                'confirmer:id,name,email',
            ])
            ->orderByDesc('id');

        if (! $user->hasPermission('trip.view_all')) {
            $tripIds = TripVisibility::visibleTripsQuery($user)->pluck('id');
            $q->whereIn('trip_id', $tripIds);
        }

        if (isset($data['trip_id'])) {
            $trip = Trip::query()->find($data['trip_id']);
            abort_unless($trip, 404);
            abort_unless(TripVisibility::userCanViewTrip($user, $trip), 403);
        }

        $q->when(isset($data['status']), fn (Builder $b) => $b->where('status', $data['status']));
        $q->when(isset($data['trip_id']), fn (Builder $b) => $b->where('trip_id', $data['trip_id']));
        $q->when(isset($data['from']), fn (Builder $b) => $b->where('created_at', '>=', Carbon::parse($data['from'])->startOfDay()));
        $q->when(isset($data['to']), fn (Builder $b) => $b->where('created_at', '<=', Carbon::parse($data['to'])->endOfDay()));

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

    public function costsForTrip(ListTripCostsRequest $request, Trip $trip)
    {
        abort_unless(TripVisibility::userCanViewTrip($request->user(), $trip), 403);

        $data = $request->validated();

        $q = $trip->costs()
            ->with(['creator:id,name,email', 'confirmer:id,name,email'])
            ->orderByDesc('id');

        $q->when(isset($data['status']), fn (Builder $b) => $b->where('status', $data['status']));

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

    public function store(SubmitTripCostRequest $request, Trip $trip)
    {
        $data = $request->validated();

        $user = $request->user();

        $trip->refresh();
        FinancialDataLock::assertTripNotPaid($trip);

        $cost = TripCost::create([
            ...$data,
            'trip_id' => $trip->id,
            'currency' => $data['currency'] ?? 'VND',
            'created_by' => $user->id,
            'status' => 'submitted',
        ]);

        app(AuditLogger::class)->log(
            actorId: $user->id,
            event: 'cost.submit',
            auditable: $cost,
            before: null,
            after: $cost->toArray(),
            metadata: ['trip_id' => $trip->id],
        );

        return $this->created($cost);
    }

    public function decide(DecideTripCostRequest $request, TripCost $tripCost)
    {
        $data = $request->validated();

        $user = $request->user();

        return DB::transaction(function () use ($tripCost, $data, $user) {
            $before = $tripCost->toArray();

            $tripCost->load('trip');
            FinancialDataLock::assertTripNotPaid($tripCost->trip);

            if (! in_array($tripCost->status, ['submitted', 'draft'], true)) {
                abort(409, Messages::COST_NOT_ACTIONABLE);
            }

            if ($data['decision'] === 'reject') {
                $tripCost->update([
                    'status' => 'rejected',
                    'confirmed_by' => $user->id,
                    'confirmed_at' => now(),
                    'rejection_reason' => $data['reason'] ?? null,
                ]);

                app(AuditLogger::class)->log(
                    actorId: $user->id,
                    event: 'cost.reject',
                    auditable: $tripCost,
                    before: $before,
                    after: $tripCost->toArray(),
                    metadata: ['reason' => $data['reason'] ?? null],
                );

                return $this->ok($tripCost);
            }

            $tripCost->update([
                'status' => 'confirmed',
                'confirmed_by' => $user->id,
                'confirmed_at' => now(),
                'rejection_reason' => null,
            ]);

            app(AuditLogger::class)->log(
                actorId: $user->id,
                event: 'cost.confirm',
                auditable: $tripCost,
                before: $before,
                after: $tripCost->toArray(),
            );

            return $this->ok($tripCost);
        });
    }

    public function override(OverrideTripCostRequest $request, TripCost $tripCost)
    {
        $data = $request->validated();

        $user = $request->user();

        return DB::transaction(function () use ($tripCost, $data, $user) {
            $before = $tripCost->toArray();

            $tripCost->fill([
                'amount' => $data['amount'] ?? $tripCost->amount,
                'description' => $data['description'] ?? $tripCost->description,
                'type' => $data['type'] ?? $tripCost->type,
            ]);
            $tripCost->save();

            app(AuditLogger::class)->log(
                actorId: $user->id,
                event: 'cost.override',
                auditable: $tripCost,
                before: $before,
                after: $tripCost->toArray(),
                metadata: ['reason' => $data['reason']],
            );

            return $this->ok($tripCost);
        });
    }
}
