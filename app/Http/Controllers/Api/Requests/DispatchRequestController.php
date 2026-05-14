<?php

namespace App\Http\Controllers\Api\Requests;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Requests\CreateDispatchRequestRequest;
use App\Http\Requests\Api\Requests\DecideDispatchRequestRequest;
use App\Http\Requests\Api\Requests\DeptDecideDispatchRequestRequest;
use App\Http\Requests\Api\Requests\ExportDispatchRequestPdfRequest;
use App\Http\Requests\Api\Requests\FillPriceDispatchRequestRequest;
use App\Http\Requests\Api\Requests\MarkDispatchRequestPaperReceivedRequest;
use App\Http\Requests\Api\Requests\RevertDispatchRequestPaperRequest;
use App\Http\Requests\Api\Requests\ShowDispatchRequestRequest;
use App\Http\Requests\Api\Requests\UpdateRecurringDispatchRequestPassengerCountRequest;
use App\Models\DispatchRequest;
use App\Models\DispatchSetting;
use App\Models\Role;
use App\Models\Trip;
use App\Models\User;
use App\Notifications\DeptHeadApprovalRequestedNotification;
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
            'priceFiller:id,name,email,employee_code',
            'trip',
            'dispatchRequestTemplate.dispatchPackage',
            'attachments' => fn ($q) => $q->orderByDesc('id'),
        ]);

        $dispatchRequest->makeVisible(['wizard_snapshot']);

        return $this->ok($this->presentDispatchRequest($dispatchRequest));
    }

    public function store(CreateDispatchRequestRequest $request)
    {
        $data = $request->validated();

        $departAt = Carbon::parse($data['depart_at']);
        $clientUrgent = (bool) ($data['is_urgent'] ?? false);
        [$finalUrgent, $urgentTrigger] = DispatchRequest::resolveUrgentTrigger(
            $data['trip_type'],
            $departAt,
            $clientUrgent,
        );
        if (! $finalUrgent && $departAt->lt(now()->addHours(2))) {
            abort(422, Messages::REQUEST_MUST_BE_2H_AHEAD);
        }

        $user = $request->user();

        $requesterId = $user->id;
        if (! empty($data['requester_id']) && ($user->hasRole('dispatcher') || $user->hasRole('admin'))) {
            $requesterId = (int) $data['requester_id'];
        }

        $urgentReasonTrim = isset($data['urgent_reason']) ? trim((string) $data['urgent_reason']) : '';

        $dispatchRequest = DispatchRequest::create([
            ...$data,
            'requester_id' => $requesterId,
            'status' => 'pending',
            'source_channel' => $data['source_channel'] ?? 'portal',
            'is_urgent' => $finalUrgent,
            'urgent_reason' => $finalUrgent ? ($urgentReasonTrim !== '' ? $urgentReasonTrim : null) : null,
            'urgent_trigger' => $urgentTrigger,
            'paper_status' => 'pending',
        ]);

        app(AuditLogger::class)->log(
            actorId: $user->id,
            event: 'request.create',
            auditable: $dispatchRequest,
            before: null,
            after: $dispatchRequest->toArray(),
        );

        if ($dispatchRequest->is_urgent) {
            app(AuditLogger::class)->log(
                actorId: $user->id,
                event: 'request.urgent_marked',
                auditable: $dispatchRequest,
                before: null,
                after: null,
                metadata: [
                    'trigger' => $dispatchRequest->urgent_trigger,
                    'requester_id' => $dispatchRequest->requester_id,
                ],
            );
        }

        if (Role::query()->where('name', 'dispatcher')->where('guard_name', 'web')->exists()) {
            $recipients = User::query()
                ->role('dispatcher')
                ->get();
            if ($recipients->isNotEmpty()) {
                $summary = trim(($dispatchRequest->origin ?? '').' → '.($dispatchRequest->destination ?? ''));
                Notification::send(
                    $recipients,
                    new NewDispatchRequestNotification(
                        $dispatchRequest->id,
                        $summary !== '→' ? $summary : 'Yêu cầu #'.$dispatchRequest->id,
                        $dispatchRequest->is_urgent,
                    ),
                );
            }
        }

        return $this->created($this->presentDispatchRequest($dispatchRequest));
    }

    public function fillPrice(FillPriceDispatchRequestRequest $request, DispatchRequest $dispatchRequest)
    {
        if ($dispatchRequest->trashed()) {
            abort(404);
        }

        $this->authorize('view', $dispatchRequest);

        $data = $request->validated();

        if ($dispatchRequest->trip_type === 'door_to_door') {
            abort(422, Messages::REQUEST_FILL_PRICE_NOT_APPLICABLE);
        }

        if ($dispatchRequest->status !== 'pending') {
            abort(409, Messages::REQUEST_NOT_PENDING);
        }

        $user = $request->user();
        $before = $dispatchRequest->toArray();

        $dispatchRequest->update([
            'status' => 'price_filled',
            'service_price' => $data['service_price'],
            'price_filled_by' => $user->id,
            'price_filled_at' => now(),
        ]);

        app(AuditLogger::class)->log(
            actorId: $user->id,
            event: 'request.price_filled',
            auditable: $dispatchRequest,
            before: $before,
            after: $dispatchRequest->fresh()->toArray(),
            metadata: [
                'service_price' => $data['service_price'],
            ],
        );

        if (Role::query()->where('name', 'department_head')->where('guard_name', 'web')->exists()) {
            $dispatchRequest->loadMissing('requester:id,department_id');
            $requesterDeptId = $dispatchRequest->requester?->department_id;
            if ($requesterDeptId !== null) {
                $summary = trim(($dispatchRequest->origin ?? '').' → '.($dispatchRequest->destination ?? ''));
                $summaryLine = $summary !== '→' ? $summary : 'Yêu cầu #'.$dispatchRequest->id;

                $recipients = User::query()
                    ->role('department_head')
                    ->where('department_id', (int) $requesterDeptId)
                    ->get();
                if ($recipients->isNotEmpty()) {
                    Notification::send(
                        $recipients,
                        new DeptHeadApprovalRequestedNotification(
                            $dispatchRequest->id,
                            $summaryLine,
                        ),
                    );
                }
            }
        }

        return $this->ok($this->presentDispatchRequest($dispatchRequest->fresh()));
    }

    public function exportPdf(ExportDispatchRequestPdfRequest $request, DispatchRequest $dispatchRequest)
    {
        if ($dispatchRequest->trashed()) {
            abort(404);
        }

        $this->authorize('view', $dispatchRequest);

        $data = DispatchRequestPdfPresenter::buildPdfData($dispatchRequest);

        logger()->debug('PDF trip_type', [
            'raw' => $dispatchRequest->trip_type,
            'isCargo' => $data['isCargo'],
            'isP2P' => $data['isP2P'],
            'isBusiness' => $data['isBusiness'],
            'isDoor' => $data['isDoor'],
        ]);

        return Pdf::loadView('pdf.dispatch-request', $data)
            ->setPaper('a4', $data['isCargo'] ? 'landscape' : 'portrait')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'enable_unicode' => true,
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
            ], true)
            ->stream('de-nghi-dieu-van-'.$dispatchRequest->id.'.pdf');
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

            $usesDeptPriceFlow = $dispatchRequest->trip_type !== 'door_to_door';

            if ($usesDeptPriceFlow) {
                if ($dispatchRequest->status !== 'price_filled') {
                    abort(409, Messages::REQUEST_NOT_PRICE_FILLED);
                }
            } elseif ($dispatchRequest->status !== 'pending') {
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

    public function deptDecision(DeptDecideDispatchRequestRequest $request, DispatchRequest $dispatchRequest)
    {
        if ($dispatchRequest->trashed()) {
            abort(404);
        }

        $this->authorize('view', $dispatchRequest);

        if ($dispatchRequest->trip_type === 'door_to_door') {
            abort(422, Messages::REQUEST_FILL_PRICE_NOT_APPLICABLE);
        }

        $data = $request->validated();
        $user = $request->user();

        return DB::transaction(function () use ($dispatchRequest, $data, $user) {
            $before = $dispatchRequest->toArray();

            if ($dispatchRequest->status !== 'price_filled') {
                abort(409, Messages::REQUEST_NOT_PRICE_FILLED);
            }

            if ($data['decision'] === 'reject') {
                $dispatchRequest->update([
                    'status' => 'rejected',
                    'approved_by' => $user->id,
                    'rejection_reason' => trim((string) ($data['rejection_reason'] ?? '')),
                ]);

                app(AuditLogger::class)->log(
                    actorId: $user->id,
                    event: 'request.dept_reject',
                    auditable: $dispatchRequest,
                    before: $before,
                    after: $dispatchRequest->toArray(),
                    metadata: ['reason' => $dispatchRequest->rejection_reason],
                );

                return $this->ok($dispatchRequest->fresh());
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
                event: 'request.approve_dept',
                auditable: $dispatchRequest,
                before: $before,
                after: $dispatchRequest->toArray(),
                metadata: ['trip_id' => $trip->id],
            );

            return $this->ok(['request' => $dispatchRequest->fresh(), 'trip' => $trip]);
        });
    }

    public function patchRecurringPassengerCount(UpdateRecurringDispatchRequestPassengerCountRequest $request, DispatchRequest $dispatchRequest)
    {
        if ($dispatchRequest->trashed()) {
            abort(404);
        }

        $data = $request->validated();
        $user = $request->user();

        $before = $dispatchRequest->toArray();
        $beforeCount = $dispatchRequest->passenger_count;

        $dispatchRequest->update([
            'passenger_count' => $data['passenger_count'],
        ]);

        app(AuditLogger::class)->log(
            actorId: $user->id,
            event: 'request.passenger_count_updated',
            auditable: $dispatchRequest,
            before: $before,
            after: $dispatchRequest->fresh()->toArray(),
            metadata: ['passenger_count_before' => $beforeCount, 'passenger_count_after' => $data['passenger_count']],
        );

        return $this->ok($this->presentDispatchRequest($dispatchRequest->fresh()));
    }

    /**
     * @return array<string, mixed>
     */
    private function presentDispatchRequest(DispatchRequest $dispatchRequest): array
    {
        $dispatchRequest->loadMissing('dispatchRequestTemplate.dispatchPackage');

        $arr = $dispatchRequest->toArray();
        $arr['threshold_hours'] = DispatchSetting::urgentThresholdHoursForTripType((string) $dispatchRequest->trip_type);
        $arr['is_urgent_auto'] = $dispatchRequest->isUrgentAuto();

        if ($dispatchRequest->dispatch_request_template_id !== null) {
            $pkg = $dispatchRequest->dispatchRequestTemplate?->dispatchPackage;
            $arr['dispatch_package_sessions'] = null;
            if ($pkg !== null) {
                $arr['dispatch_package_sessions'] = [
                    'dispatch_package_id' => $pkg->id,
                    'total_sessions' => (int) $pkg->total_sessions,
                    'sessions_used' => (int) $pkg->sessions_used,
                    'sessions_remaining' => $pkg->remainingSessions(),
                    'alert_when_remaining_sessions' => (int) $pkg->alert_when_remaining_sessions,
                ];
            }
        }

        return $arr;
    }
}
