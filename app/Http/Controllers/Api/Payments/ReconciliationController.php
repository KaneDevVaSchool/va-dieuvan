<?php

namespace App\Http\Controllers\Api\Payments;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Payments\CreateReconciliationPeriodRequest;
use App\Http\Requests\Api\Payments\ExecutePaymentRequest;
use App\Http\Requests\Api\Payments\GeneratePaymentsRequest;
use App\Http\Requests\Api\Payments\ListPaymentsRequest;
use App\Http\Requests\Api\Payments\ListReconciliationPeriodsRequest;
use App\Http\Requests\Api\Payments\LockReconciliationPeriodRequest;
use App\Http\Requests\Api\Payments\ShowPaymentRequest;
use App\Http\Requests\Api\Payments\ShowReconciliationPeriodRequest;
use App\Models\Payment;
use App\Models\ReconciliationPeriod;
use App\Models\Trip;
use App\Services\Auditing\AuditLogger;
use App\Support\Messages;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReconciliationController extends Controller
{
    use ApiResponses;

    public function periodsIndex(ListReconciliationPeriodsRequest $request)
    {
        $data = $request->validated();

        $q = ReconciliationPeriod::query()
            ->with(['creator:id,name,email'])
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

    public function periodShow(ShowReconciliationPeriodRequest $request, ReconciliationPeriod $reconciliationPeriod)
    {
        $reconciliationPeriod->load([
            'creator:id,name,email',
            'payments' => fn ($q) => $q->orderByDesc('id')->limit(200),
        ]);

        return $this->ok($reconciliationPeriod);
    }

    public function paymentsIndex(ListPaymentsRequest $request)
    {
        $data = $request->validated();

        $q = Payment::query()
            ->with([
                'trip:id,status,depart_at,payment_status',
                'period:id,start_date,end_date,status',
                'executor:id,name,email',
            ])
            ->orderByDesc('id');

        $q->when(isset($data['status']), fn (Builder $b) => $b->where('status', $data['status']));
        $q->when(isset($data['reconciliation_period_id']), fn (Builder $b) => $b->where('reconciliation_period_id', $data['reconciliation_period_id']));

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

    public function paymentShow(ShowPaymentRequest $request, Payment $payment)
    {
        $payment->load([
            'trip.dispatchRequest',
            'period',
            'executor:id,name,email',
        ]);

        return $this->ok($payment);
    }

    public function createPeriod(CreateReconciliationPeriodRequest $request)
    {
        $data = $request->validated();

        $user = $request->user();

        $period = ReconciliationPeriod::create([
            'start_date' => Carbon::parse($data['start_date'])->toDateString(),
            'end_date' => Carbon::parse($data['end_date'])->toDateString(),
            'status' => 'draft',
            'created_by' => $user->id,
        ]);

        app(AuditLogger::class)->log($user->id, 'reconcile.period.create', $period, null, $period->toArray());

        return $this->created($period);
    }

    public function lockPeriod(LockReconciliationPeriodRequest $request, ReconciliationPeriod $reconciliationPeriod)
    {
        $user = $request->user();

        if ($reconciliationPeriod->status !== 'draft') {
            abort(409, Messages::RECONCILE_PERIOD_NOT_DRAFT);
        }

        $before = $reconciliationPeriod->toArray();

        $reconciliationPeriod->update([
            'status' => 'locked',
            'locked_by' => $user->id,
            'locked_at' => now(),
        ]);

        app(AuditLogger::class)->log($user->id, 'reconcile.period.lock', $reconciliationPeriod, $before, $reconciliationPeriod->toArray());

        return $this->ok($reconciliationPeriod);
    }

    public function generatePayments(GeneratePaymentsRequest $request, ReconciliationPeriod $reconciliationPeriod)
    {
        $data = $request->validated();

        if ($reconciliationPeriod->status !== 'locked') {
            abort(409, Messages::RECONCILE_PERIOD_NOT_LOCKED);
        }

        $user = $request->user();

        return DB::transaction(function () use ($data, $reconciliationPeriod, $user) {
            $created = [];
            foreach ($data['trip_ids'] as $tripId) {
                /** @var Trip $trip */
                $trip = Trip::findOrFail($tripId);

                if (($trip->payment_status ?? 'unpaid') === 'paid') {
                    abort(422, Messages::TRIP_PAID_CANNOT_ADD_TO_RECONCILE);
                }

                $amount = $trip->costs()->where('status', 'confirmed')->sum('amount');

                $payment = Payment::updateOrCreate(
                    ['trip_id' => $trip->id],
                    [
                        'reconciliation_period_id' => $reconciliationPeriod->id,
                        'status' => 'pending',
                        'amount' => $amount,
                        'currency' => 'VND',
                    ],
                );

                $created[] = $payment->id;
            }

            app(AuditLogger::class)->log(
                actorId: $user->id,
                event: 'payment.generate',
                auditable: $reconciliationPeriod,
                before: null,
                after: ['payment_ids' => $created],
            );

            return $this->ok(['payment_ids' => $created]);
        });
    }

    public function executePayment(ExecutePaymentRequest $request, Payment $payment)
    {
        $data = $request->validated();

        $user = $request->user();

        $before = $payment->toArray();

        $payment->update([
            'status' => $data['status'],
            'method' => $data['method'],
            'reference' => $data['reference'] ?? null,
            'executed_by' => $user->id,
            'executed_at' => now(),
        ]);

        // Sync Trip payment fields (BRD: Trip -> Đã thanh toán)
        $trip = $payment->trip;
        if ($trip) {
            $tripBefore = $trip->only(['payment_status', 'paid_at']);
            $trip->update([
                'payment_status' => $data['status'] === 'paid' ? 'paid' : 'pending',
                'paid_at' => $data['status'] === 'paid' ? now() : null,
            ]);
            app(AuditLogger::class)->log($user->id, 'trip.payment_sync', $trip, $tripBefore, $trip->only(['payment_status', 'paid_at']));
        }

        app(AuditLogger::class)->log($user->id, 'payment.execute', $payment, $before, $payment->toArray());

        return $this->ok($payment);
    }
}
