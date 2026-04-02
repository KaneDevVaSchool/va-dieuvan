<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\ReconciliationPeriod;
use App\Models\Trip;
use App\Services\Auditing\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ReconciliationController extends Controller
{
    public function createPeriod(Request $request)
    {
        $data = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $user = $request->user();

        $period = ReconciliationPeriod::create([
            'start_date' => Carbon::parse($data['start_date'])->toDateString(),
            'end_date' => Carbon::parse($data['end_date'])->toDateString(),
            'status' => 'draft',
            'created_by' => $user->id,
        ]);

        app(AuditLogger::class)->log($user->id, 'reconcile.period.create', $period, null, $period->toArray());

        return response()->json(['data' => $period], 201);
    }

    public function lockPeriod(Request $request, ReconciliationPeriod $reconciliationPeriod)
    {
        $user = $request->user();

        if ($reconciliationPeriod->status !== 'draft') {
            abort(409, 'Kỳ đối soát không ở trạng thái draft.');
        }

        $before = $reconciliationPeriod->toArray();

        $reconciliationPeriod->update([
            'status' => 'locked',
            'locked_by' => $user->id,
            'locked_at' => now(),
        ]);

        app(AuditLogger::class)->log($user->id, 'reconcile.period.lock', $reconciliationPeriod, $before, $reconciliationPeriod->toArray());

        return response()->json(['data' => $reconciliationPeriod]);
    }

    public function generatePayments(Request $request, ReconciliationPeriod $reconciliationPeriod)
    {
        $data = $request->validate([
            'trip_ids' => ['required', 'array', 'min:1'],
            'trip_ids.*' => ['integer', 'min:1'],
        ]);

        if ($reconciliationPeriod->status !== 'locked') {
            abort(409, 'Chỉ tạo payment khi kỳ đối soát đã locked.');
        }

        $user = $request->user();

        return DB::transaction(function () use ($data, $reconciliationPeriod, $user) {
            $created = [];
            foreach ($data['trip_ids'] as $tripId) {
                /** @var Trip $trip */
                $trip = Trip::findOrFail($tripId);

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

            return response()->json(['data' => ['payment_ids' => $created]]);
        });
    }

    public function executePayment(Request $request, Payment $payment)
    {
        $data = $request->validate([
            'method' => ['required', 'string', 'max:50'],
            'reference' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['paid', 'failed', 'cancelled'])],
        ]);

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

        return response()->json(['data' => $payment]);
    }
}

