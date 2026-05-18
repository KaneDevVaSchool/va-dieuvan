<?php

namespace App\Http\Controllers\Api\Costs;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Costs\DecideTripCostRequest;
use App\Http\Requests\Api\Costs\DestroyTripCostByDriverRequest;
use App\Http\Requests\Api\Costs\ListAllTripCostsRequest;
use App\Http\Requests\Api\Costs\ListTripCostsRequest;
use App\Http\Requests\Api\Costs\OverrideTripCostRequest;
use App\Http\Requests\Api\Costs\ShowTripCostRequest;
use App\Http\Requests\Api\Costs\SubmitTripCostRequest;
use App\Http\Requests\Api\Costs\UpdateTripCostByDriverRequest;
use App\Http\Requests\Api\Costs\UploadTripCostReceiptRequest;
use App\Models\Trip;
use App\Models\TripCost;
use App\Services\Auditing\AuditLogger;
use App\Support\FinancialDataLock;
use App\Support\Messages;
use App\Support\TripVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TripCostController extends Controller
{
    use ApiResponses;

    public function index(ListAllTripCostsRequest $request)
    {
        $data = $request->validated();
        $user = $request->user();

        $q = TripCost::query()
            ->with([
                'trip:id,status,depart_at,dispatcher_id,driver_id,transport_provider_id',
                'trip.transportProvider:id,name',
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
        $q->when(isset($data['type']), fn (Builder $b) => $b->where('type', $data['type']));
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
        FinancialDataLock::assertTripAllowsPassengerAndCostEdits($trip);

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

    public function show(ShowTripCostRequest $request, TripCost $tripCost)
    {
        $tripCost->load([
            'attachments' => fn ($q) => $q->orderByDesc('id'),
            'creator:id,name,email',
            'confirmer:id,name,email',
            'trip',
            'trip.dispatchRequest:id,origin,destination,status,arrive_by',
        ]);

        abort_unless($tripCost->relationLoaded('trip') && $tripCost->trip, 404);
        abort_unless(TripVisibility::userCanViewTrip($request->user(), $tripCost->trip), 403);

        return $this->ok($tripCost);
    }

    public function updateByDriver(UpdateTripCostByDriverRequest $request, TripCost $tripCost)
    {
        $user = $request->user();
        abort_unless((int) $tripCost->created_by === (int) $user->id, 403);
        abort_unless(in_array($tripCost->status, ['draft', 'submitted'], true), 409, Messages::COST_NOT_ACTIONABLE);

        $tripCost->load('trip');
        FinancialDataLock::assertTripNotPaid($tripCost->trip);
        FinancialDataLock::assertTripAllowsPassengerAndCostEdits($tripCost->trip);

        $data = $request->validated();
        $before = $tripCost->toArray();
        $tripCost->update([
            'type' => $data['type'],
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'VND',
            'description' => $data['description'] ?? null,
        ]);

        app(AuditLogger::class)->log(
            actorId: $user->id,
            event: 'cost.driver_update',
            auditable: $tripCost,
            before: $before,
            after: $tripCost->toArray(),
        );

        return $this->ok($tripCost);
    }

    public function destroyByDriver(DestroyTripCostByDriverRequest $request, TripCost $tripCost)
    {
        $user = $request->user();
        $isReconciler = $user->hasPermission('trip.cost.reconcile');

        if (! $isReconciler) {
            abort_unless((int) $tripCost->created_by === (int) $user->id, 403);
            abort_unless(in_array($tripCost->status, ['draft', 'submitted'], true), 409, Messages::COST_NOT_ACTIONABLE);
        }

        $tripCost->load('trip');
        FinancialDataLock::assertTripNotPaid($tripCost->trip);

        if (! $isReconciler) {
            FinancialDataLock::assertTripAllowsPassengerAndCostEdits($tripCost->trip);
        }

        $deletedId = $tripCost->id;
        $before = $tripCost->toArray();
        $relatedTrip = $tripCost->trip;
        $tripCost->delete();

        app(AuditLogger::class)->log(
            actorId: $user->id,
            event: $isReconciler ? 'cost.manager_delete' : 'cost.driver_delete',
            auditable: $relatedTrip,
            before: $before,
            after: null,
            metadata: ['deleted_trip_cost_id' => $deletedId],
        );

        return $this->ok(['deleted' => true, 'id' => $deletedId]);
    }

    public function decide(DecideTripCostRequest $request, TripCost $tripCost)
    {
        $data = $request->validated();

        $user = $request->user();

        return DB::transaction(function () use ($tripCost, $data, $user) {
            $before = $tripCost->toArray();

            $tripCost->load('trip');
            // Duyệt / từ chối đối soát vẫn được phép khi chuyến đã hoàn thành hoặc hủy (chỉ chặn khi đã thanh toán / khóa tài chính).
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
            $tripCost->loadMissing('trip');
            FinancialDataLock::assertTripAllowsPassengerAndCostEdits($tripCost->trip);

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

    public function uploadReceiptForTrip(UploadTripCostReceiptRequest $request, Trip $trip, TripCost $tripCost)
    {
        abort_if((int) $tripCost->trip_id !== (int) $trip->id, 404);

        abort_unless(TripVisibility::userCanViewTrip($request->user(), $trip), 403);

        $trip->refresh();
        FinancialDataLock::assertTripNotPaid($trip);
        FinancialDataLock::assertTripAllowsPassengerAndCostEdits($trip);

        /** @var \Illuminate\Http\UploadedFile $file */
        $file = $request->file('file');
        $path = Storage::disk('public')->putFile("attachments/costs/{$tripCost->id}", $file);
        $tripCost->receipt_url = Storage::disk('public')->url($path);
        $tripCost->save();

        app(AuditLogger::class)->log(
            actorId: $request->user()->id,
            event: 'cost.receipt_upload',
            auditable: $tripCost,
            before: null,
            after: ['receipt_url' => $tripCost->receipt_url],
        );

        return $this->ok($tripCost);
    }
}
