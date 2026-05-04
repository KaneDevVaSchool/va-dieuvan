<?php

namespace App\Http\Controllers\Api\Requests;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Requests\CreateDispatchRequestRequest;
use App\Http\Requests\Api\Requests\DecideDispatchRequestRequest;
use App\Http\Requests\Api\Requests\ExportDispatchRequestPdfRequest;
use App\Http\Requests\Api\Requests\MarkDispatchRequestPaperReceivedRequest;
use App\Http\Requests\Api\Requests\RevertDispatchRequestPaperRequest;
use App\Http\Requests\Api\Requests\ShowDispatchRequestRequest;
use App\Models\DispatchRequest;
use App\Models\Role;
use App\Models\Trip;
use App\Models\User;
use App\Notifications\NewDispatchRequestNotification;
use App\Services\Auditing\AuditLogger;
use App\Services\DispatchRequests\DispatchRequestPdfPresenter;
use App\Support\DispatchCargoShipmentProvisioner;
use App\Support\Messages;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class DispatchRequestController extends Controller
{
    use ApiResponses;
    use AuthorizesRequests;

    public function show(ShowDispatchRequestRequest $request, DispatchRequest $dispatchRequest)
    {
        $this->authorize('view', $dispatchRequest);

        $dispatchRequest->load([
            'requester:id,name,email,employee_code,avatar_url',
            'approver:id,name,email,employee_code',
            'trip',
            'attachments' => fn ($q) => $q->orderByDesc('id'),
        ]);

        $dispatchRequest->makeVisible(['wizard_snapshot']);

        return $this->ok($dispatchRequest);
    }

    public function store(CreateDispatchRequestRequest $request)
    {
        $data = $request->validated();

        $departAt = Carbon::parse($data['depart_at']);
        $isUrgent = (bool) ($data['is_urgent'] ?? false);
        if (! $isUrgent && $departAt->lt(now()->addHours(2))) {
            abort(422, Messages::REQUEST_MUST_BE_2H_AHEAD);
        }

        $user = $request->user();

        $requesterId = $user->id;
        if (! empty($data['requester_id']) && ($user->hasRole('dispatcher') || $user->hasRole('admin'))) {
            $requesterId = (int) $data['requester_id'];
        }

        $dispatchRequest = DispatchRequest::create([
            ...$data,
            'requester_id' => $requesterId,
            'status' => 'pending',
            'source_channel' => $data['source_channel'] ?? 'portal',
            'is_urgent' => $isUrgent,
            'paper_status' => 'pending',
        ]);

        app(AuditLogger::class)->log(
            actorId: $user->id,
            event: 'request.create',
            auditable: $dispatchRequest,
            before: null,
            after: $dispatchRequest->toArray(),
        );

        if (Role::query()->where('name', 'dispatcher')->where('guard_name', 'web')->exists()) {
            $recipients = User::query()
                ->role('dispatcher')
                ->get();
            if ($recipients->isNotEmpty()) {
                $summary = trim(($dispatchRequest->origin ?? '').' → '.($dispatchRequest->destination ?? ''));
                Notification::send(
                    $recipients,
                    new NewDispatchRequestNotification($dispatchRequest->id, $summary !== '→' ? $summary : 'Yêu cầu #'.$dispatchRequest->id),
                );
            }
        }

        return $this->created($dispatchRequest);
    }

    public function exportPdf(ExportDispatchRequestPdfRequest $request, DispatchRequest $dispatchRequest)
    {
        if ($dispatchRequest->trashed()) {
            abort(404);
        }

        $data = DispatchRequestPdfPresenter::forModel($dispatchRequest);

        return Pdf::loadView('pdf.dispatch-request', $data)
            ->setPaper('a4', 'landscape')
            ->stream('de-nghi-dieu-van.pdf', ['Attachment' => false]);
    }

    public function markPaperReceived(MarkDispatchRequestPaperReceivedRequest $request, DispatchRequest $dispatchRequest)
    {
        if ($dispatchRequest->trashed()) {
            abort(404);
        }

        $data = $request->validated();

        $user = $request->user();
        $before = $dispatchRequest->toArray();

        if ($dispatchRequest->paper_status === 'received') {
            $updates = [];
            if (array_key_exists('paper_reference', $data)) {
                $ref = $data['paper_reference'];
                $updates['paper_reference'] = ($ref !== null && trim((string) $ref) !== '')
                    ? trim((string) $ref)
                    : null;
            }
            if (! empty($data['paper_received_at'])) {
                $updates['paper_received_at'] = Carbon::parse($data['paper_received_at']);
            }
            if ($updates !== []) {
                $dispatchRequest->update($updates);
                app(AuditLogger::class)->log(
                    actorId: $user->id,
                    event: 'request.paper_meta_updated',
                    auditable: $dispatchRequest,
                    before: $before,
                    after: $dispatchRequest->fresh()->toArray(),
                );
            }

            return $this->ok($dispatchRequest->fresh());
        }

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

        return $this->ok($dispatchRequest);
    }

    public function revertPaperReceived(RevertDispatchRequestPaperRequest $request, DispatchRequest $dispatchRequest)
    {
        if ($dispatchRequest->trashed()) {
            abort(404);
        }

        if ($dispatchRequest->paper_status !== 'received') {
            abort(422, Messages::REQUEST_PAPER_NOT_RECEIVED);
        }

        $user = $request->user();
        $before = $dispatchRequest->toArray();

        $dispatchRequest->update([
            'paper_status' => 'pending',
            'paper_received_at' => null,
        ]);

        app(AuditLogger::class)->log(
            actorId: $user->id,
            event: 'request.paper_reverted',
            auditable: $dispatchRequest,
            before: $before,
            after: $dispatchRequest->toArray(),
        );

        return $this->ok($dispatchRequest);
    }

    public function approve(DecideDispatchRequestRequest $request, DispatchRequest $dispatchRequest)
    {
        if ($dispatchRequest->trashed()) {
            abort(404);
        }

        $data = $request->validated();

        $user = $request->user();

        return DB::transaction(function () use ($dispatchRequest, $data, $user) {
            $before = $dispatchRequest->toArray();

            if ($dispatchRequest->status !== 'pending') {
                abort(409, Messages::REQUEST_NOT_PENDING);
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

                return $this->ok($dispatchRequest);
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

            DispatchCargoShipmentProvisioner::provision($dispatchRequest, $trip);

            app(AuditLogger::class)->log(
                actorId: $user->id,
                event: 'request.approve',
                auditable: $dispatchRequest,
                before: $before,
                after: $dispatchRequest->toArray(),
                metadata: ['trip_id' => $trip->id],
            );

            return $this->ok(['request' => $dispatchRequest, 'trip' => $trip]);
        });
    }
}
