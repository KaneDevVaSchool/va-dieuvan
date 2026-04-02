<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DispatchRequest;
use App\Models\Trip;
use App\Services\Auditing\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DispatchRequestController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'trip_type' => ['required', Rule::in(['door_to_door', 'point_to_point', 'business', 'cargo'])],
            'origin' => ['nullable', 'string', 'max:255'],
            'destination' => ['nullable', 'string', 'max:255'],
            'depart_at' => ['required', 'date'],
            'arrive_by' => ['nullable', 'date', 'after_or_equal:depart_at'],
            'passenger_count' => ['nullable', 'integer', 'min:1', 'max:999'],
            'notes' => ['nullable', 'string'],
            'source_channel' => ['nullable', Rule::in(['portal', 'zalo', 'paper'])],
            'is_urgent' => ['nullable', 'boolean'],
            // Dispatcher/Admin có thể tạo thay cho người đề xuất (nhận từ Zalo)
            'requester_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $departAt = Carbon::parse($data['depart_at']);
        $isUrgent = (bool) ($data['is_urgent'] ?? false);
        // BR-001: yêu cầu tạo trước ít nhất 2 tiếng (trừ trường hợp gấp)
        if (!$isUrgent && $departAt->lt(now()->addHours(2))) {
            abort(422, 'Yêu cầu phải được tạo trước giờ xuất phát ít nhất 2 tiếng (trừ lệnh gấp).');
        }

        $user = $request->user();

        $requesterId = $user->id;
        if (!empty($data['requester_id']) && ($user->hasRole('dispatcher') || $user->hasRole('admin'))) {
            $requesterId = (int) $data['requester_id'];
        }

        $dispatchRequest = DispatchRequest::create([
            ...$data,
            'requester_id' => $requesterId,
            'status' => 'pending',
            'source_channel' => $data['source_channel'] ?? 'portal',
            'is_urgent' => $isUrgent,
            // Luồng phiếu giấy: mặc định pending (có thể nhận sau)
            'paper_status' => 'pending',
        ]);

        app(AuditLogger::class)->log(
            actorId: $user->id,
            event: 'request.create',
            auditable: $dispatchRequest,
            before: null,
            after: $dispatchRequest->toArray(),
        );

        return response()->json(['data' => $dispatchRequest], 201);
    }

    public function markPaperReceived(Request $request, DispatchRequest $dispatchRequest)
    {
        $data = $request->validate([
            'paper_received_at' => ['nullable', 'date'],
            'paper_reference' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $before = $dispatchRequest->toArray();

        $dispatchRequest->update([
            'paper_status' => 'received',
            'paper_received_at' => isset($data['paper_received_at']) ? Carbon::parse($data['paper_received_at']) : now(),
            'paper_reference' => $data['paper_reference'] ?? $dispatchRequest->paper_reference,
        ]);

        app(AuditLogger::class)->log(
            actorId: $user->id,
            event: 'request.paper_received',
            auditable: $dispatchRequest,
            before: $before,
            after: $dispatchRequest->toArray(),
        );

        return response()->json(['data' => $dispatchRequest]);
    }

    public function approve(Request $request, DispatchRequest $dispatchRequest)
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();

        return DB::transaction(function () use ($dispatchRequest, $data, $user) {
            $before = $dispatchRequest->toArray();

            if ($dispatchRequest->status !== 'pending') {
                abort(409, 'Yêu cầu không ở trạng thái chờ duyệt.');
            }

            if ($data['decision'] === 'reject') {
                $dispatchRequest->update([
                    'status' => 'rejected',
                    'approved_by' => $user->id,
                    'rejection_reason' => $data['reason'] ?? null,
                ]);

                app(AuditLogger::class)->log(
                    actorId: $user->id,
                    event: 'request.reject',
                    auditable: $dispatchRequest,
                    before: $before,
                    after: $dispatchRequest->toArray(),
                    metadata: ['reason' => $data['reason'] ?? null],
                );

                return response()->json(['data' => $dispatchRequest]);
            }

            $dispatchRequest->update([
                'status' => 'approved',
                'approved_by' => $user->id,
                'rejection_reason' => null,
            ]);

            $trip = Trip::create([
                'dispatch_request_id' => $dispatchRequest->id,
                'dispatcher_id' => $user->id,
                'status' => 'approved',
                'depart_at' => $dispatchRequest->depart_at,
                'arrive_by' => $dispatchRequest->arrive_by,
                'lock_version' => 0,
            ]);

            app(AuditLogger::class)->log(
                actorId: $user->id,
                event: 'request.approve',
                auditable: $dispatchRequest,
                before: $before,
                after: $dispatchRequest->toArray(),
                metadata: ['trip_id' => $trip->id],
            );

            return response()->json(['data' => ['request' => $dispatchRequest, 'trip' => $trip]]);
        });
    }
}

