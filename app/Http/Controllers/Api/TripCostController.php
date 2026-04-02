<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\TripCost;
use App\Services\Auditing\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TripCostController extends Controller
{
    public function store(Request $request, Trip $trip)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['fuel', 'toll', 'parking', 'other'])],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'description' => ['nullable', 'string', 'max:255'],
            'receipt_url' => ['nullable', 'string', 'max:2048'],
        ]);

        $user = $request->user();

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

        return response()->json(['data' => $cost], 201);
    }

    public function decide(Request $request, TripCost $tripCost)
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['confirm', 'reject'])],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();

        return DB::transaction(function () use ($tripCost, $data, $user) {
            $before = $tripCost->toArray();

            if (!in_array($tripCost->status, ['submitted', 'draft'], true)) {
                abort(409, 'Chi phí không ở trạng thái có thể xử lý.');
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

                return response()->json(['data' => $tripCost]);
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

            return response()->json(['data' => $tripCost]);
        });
    }

    public function override(Request $request, TripCost $tripCost)
    {
        $data = $request->validate([
            'amount' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', Rule::in(['fuel', 'toll', 'parking', 'other'])],
            'reason' => ['required', 'string', 'max:255'],
        ]);

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

            return response()->json(['data' => $tripCost]);
        });
    }
}

