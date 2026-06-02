<?php

namespace App\Http\Controllers\Api\Requests;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Api\Concerns\PresentsDispatchRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Requests\CloneDispatchRequestRequest;
use App\Http\Requests\Api\Requests\CreateDispatchRequestRequest;
use App\Http\Requests\Api\Requests\DecideDispatchRequestRequest;
use App\Http\Requests\Api\Requests\DeptDecideDispatchRequestRequest;
use App\Http\Requests\Api\Requests\ExportDispatchRequestPdfRequest;
use App\Http\Requests\Api\Requests\FillPriceDispatchRequestRequest;
use App\Http\Requests\Api\Requests\MarkDispatchRequestPaperReceivedRequest;
use App\Http\Requests\Api\Requests\RevertDispatchRequestPaperRequest;
use App\Http\Requests\Api\Requests\ShowDispatchRequestRequest;
use App\Http\Requests\Api\Requests\ApplyDispatchRequestPricingHintsRequest;
use App\Http\Requests\Api\Requests\UpdateDispatchRequestWizardRequest;
use App\Http\Requests\Api\Requests\UpdateRecurringDispatchRequestPassengerCountRequest;
use App\Models\AuditLog;
use App\Models\DispatchRequest;
use App\Models\Role;
use App\Models\Trip;
use App\Models\User;
use App\Notifications\DeptHeadApprovalRequestedNotification;
use App\Notifications\DeptHeadDecisionNotification;
use App\Notifications\SignedPaperUploadReminderNotification;
use App\Services\Notifications\DispatchStaffNotificationRecipients;
use App\Http\Requests\Api\Requests\SubmitRecurringDispatchRequestStudentCountRequest;
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
    use PresentsDispatchRequest;

    public function show(ShowDispatchRequestRequest $request, DispatchRequest $dispatchRequest)
    {
        $this->authorize('view', $dispatchRequest);

        $dispatchRequest->load([
            'requester:id,name,email,employee_code,avatar_url,department_id',
            'approver:id,name,email,employee_code',
            'priceFiller:id,name,email,employee_code',
            'trip',
            'dispatchRequestTemplate.dispatchPackage',
            'clonedFrom:id,status,origin,destination,created_at',
            'attachments' => fn ($q) => $q->orderByDesc('id'),
            'currentSignedVersion.attachment',
            'currentSignedVersion.uploader',
        ]);

        $dispatchRequest->makeVisible(['wizard_snapshot']);

        return $this->ok($this->presentDispatchRequest($dispatchRequest));
    }

    public function auditLogs(ShowDispatchRequestRequest $request, DispatchRequest $dispatchRequest)
    {
        $this->authorize('view', $dispatchRequest);

        if ($dispatchRequest->trashed()) {
            abort(404);
        }

        $items = AuditLog::query()
            ->where('auditable_type', $dispatchRequest->getMorphClass())
            ->where('auditable_id', $dispatchRequest->id)
            ->with(['actor:id,name,email'])
            ->orderByDesc('id')
            ->limit(80)
            ->get();

        return $this->ok(['items' => $items]);
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

        DispatchStaffNotificationRecipients::notifyNewDispatchRequest($dispatchRequest);

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

        $chosenDeptHeadId = isset($data['dept_head_user_id']) ? (int) $data['dept_head_user_id'] : null;
        if ($chosenDeptHeadId === 0) {
            $chosenDeptHeadId = null;
        }
        if ($chosenDeptHeadId !== null) {
            $eligible = User::query()
                ->where('is_active', true)
                ->role('department_head')
                ->whereKey($chosenDeptHeadId)
                ->exists();
            if (! $eligible) {
                abort(422, Messages::REQUEST_INVALID_DEPT_HEAD);
            }
        }

        $snap = $dispatchRequest->wizard_snapshot ?? [];

        $tripType = $dispatchRequest->trip_type ?? '';
        if ($tripType === 'cargo' && ! empty($data['rows'])) {
            foreach ($data['rows'] as $i => $row) {
                if (! is_array($row)) {
                    continue;
                }
                if (! isset($snap['cargoRows'][$i]) || ! is_array($snap['cargoRows'][$i])) {
                    continue;
                }
                if (array_key_exists('transport_note', $row)) {
                    $snap['cargoRows'][$i]['transport_note'] = $row['transport_note'];
                }
                if (array_key_exists('cost', $row)) {
                    $snap['cargoRows'][$i]['cost'] = $row['cost'];
                }
            }
        } elseif ($tripType !== '' && $tripType !== 'cargo' && ! empty($data['rows'])) {
            $rowKey = $tripType === 'business' ? 'businessRows' : 'passengerRows';
            foreach ($data['rows'] as $i => $row) {
                if (! is_array($row)) {
                    continue;
                }
                if (! isset($snap[$rowKey][$i]) || ! is_array($snap[$rowKey][$i])) {
                    continue;
                }
                if (array_key_exists('unit_price', $row)) {
                    $snap[$rowKey][$i]['unit_price'] = $row['unit_price'];
                }
                if (array_key_exists('extra_fee', $row)) {
                    $snap[$rowKey][$i]['extra_fee'] = $row['extra_fee'];
                }
                if (array_key_exists('notes', $row)) {
                    $snap[$rowKey][$i]['notes'] = $row['notes'];
                }
            }
        }

        $dispatchRequest->update([
            'status' => 'price_filled',
            'service_price' => $data['service_price'],
            'wizard_snapshot' => $snap,
            'price_filled_by' => $user->id,
            'price_filled_at' => now(),
            'assigned_dept_head_id' => $chosenDeptHeadId,
        ]);

        app(AuditLogger::class)->log(
            actorId: $user->id,
            event: 'request.price_filled',
            auditable: $dispatchRequest,
            before: $before,
            after: $dispatchRequest->fresh()->toArray(),
            metadata: [
                'service_price' => $data['service_price'],
                'assigned_dept_head_id' => $chosenDeptHeadId,
            ],
        );

        if ($chosenDeptHeadId !== null
            && Role::query()->where('name', 'department_head')->where('guard_name', 'web')->exists()) {
            $recipients = User::query()->whereKey($chosenDeptHeadId)->get();
            if ($recipients->isNotEmpty()) {
                Notification::send(
                    $recipients,
                    new DeptHeadApprovalRequestedNotification($dispatchRequest->id),
                );
            }
        }

        return $this->ok($this->presentDispatchRequest($dispatchRequest->fresh()));
    }

    /**
     * Trưởng BP đang hoạt động (cho bước gán người duyệt khi điền giá — có thể chọn BP bất kỳ đơn vị).
     */
    public function availableDeptHeads(ShowDispatchRequestRequest $request, DispatchRequest $dispatchRequest)
    {
        if ($dispatchRequest->trashed()) {
            abort(404);
        }

        $this->authorize('view', $dispatchRequest);

        if (! Role::query()->where('name', 'department_head')->where('guard_name', 'web')->exists()) {
            return $this->ok([]);
        }

        $qTrim = trim((string) ($request->query('q', '')));

        $users = User::query()
            ->where('is_active', true)
            ->role('department_head')
            ->when($qTrim !== '', function ($query) use ($qTrim): void {
                $like = '%'.addcslashes($qTrim, '%_\\').'%';
                $query->where(function ($w) use ($like): void {
                    $w->where('name', 'like', $like)
                        ->orWhere('employee_code', 'like', $like)
                        ->orWhere('email', 'like', $like);
                });
            })
            ->orderBy('name')
            ->limit(25)
            ->get(['id', 'name', 'employee_code', 'email']);

        $pickId = (int) ($request->query('pick', 0) ?: 0);
        if ($pickId > 0) {
            $picked = User::query()
                ->where('is_active', true)
                ->role('department_head')
                ->whereKey($pickId)
                ->first(['id', 'name', 'employee_code', 'email']);
            if ($picked !== null && ! $users->contains(static fn (User $u): bool => (int) $u->id === $pickId)) {
                $users = $users->prepend($picked)->values();
            }
        }

        return $this->ok($users);
    }

    public function exportPdf(ExportDispatchRequestPdfRequest $request, DispatchRequest $dispatchRequest)
    {
        if ($dispatchRequest->trashed()) {
            abort(404);
        }

        $this->authorize('view', $dispatchRequest);

        if ($dispatchRequest->status !== 'approved') {
            abort(403, Messages::REQUEST_PDF_REQUIRES_APPROVAL);
        }

        $data = DispatchRequestPdfPresenter::buildPdfData($dispatchRequest);

        if ($dispatchRequest->signing_workflow_status === null) {
            $dispatchRequest->forceFill(['signing_workflow_status' => 'awaiting_signature'])->saveQuietly();
        }

        app(AuditLogger::class)->log(
            actorId: $request->user()?->id,
            event: 'bm03.export',
            auditable: $dispatchRequest,
            before: null,
            after: ['signing_workflow_status' => $dispatchRequest->fresh()->signing_workflow_status],
        );

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

        $verificationWarning = null;
        if (
            config('dispatch.paper_received_requires_verified', false)
            && in_array($dispatchRequest->verification_status, ['no_signature', 'rejected'], true)
        ) {
            abort(422, 'Bản scan chưa được xác thực chữ ký.');
        }

        if (
            ! config('dispatch.paper_received_requires_verified', false)
            && in_array($dispatchRequest->verification_status, ['no_signature', 'rejected', 'manual_review'], true)
            && $dispatchRequest->paper_status !== 'received'
        ) {
            $verificationWarning = 'signed_document_verification_incomplete';
        }

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

        $fresh = $dispatchRequest->fresh();
        $payload = $this->presentDispatchRequest($fresh);
        if ($verificationWarning !== null) {
            $payload['paper_received_warning'] = $verificationWarning;
        }

        return $this->ok($payload);
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

                $this->scheduleRequesterDeptDecisionNotification($dispatchRequest->id, 'reject');

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

            $this->scheduleRequesterDeptDecisionNotification($dispatchRequest->id, 'approve');

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

                $this->scheduleRequesterDeptDecisionNotification($dispatchRequest->id, 'reject');

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

            $this->scheduleRequesterDeptDecisionNotification($dispatchRequest->id, 'approve');

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
        $beforeCount = $dispatchRequest->student_count_actual;

        $updates = ['student_count_actual' => $data['student_count_actual']];
        if ($dispatchRequest->dispatch_request_template_id !== null) {
            $updates['passenger_count'] = $data['student_count_actual'];
        }
        $dispatchRequest->update($updates);

        $event = 'request.student_count_actual_updated';
        if ($dispatchRequest->locked_at !== null && $user->hasPermission('trip.view_all')) {
            $event = 'request.student_count_actual_updated_after_lock';
        }

        app(AuditLogger::class)->log(
            actorId: $user->id,
            event: $event,
            auditable: $dispatchRequest,
            before: $before,
            after: $dispatchRequest->fresh()->toArray(),
            metadata: [
                'student_count_actual_before' => $beforeCount,
                'student_count_actual_after' => $data['student_count_actual'],
            ],
        );

        $fresh = $dispatchRequest->fresh();

        return $this->ok([
            'trip_id' => $fresh->id,
            'student_count_actual' => $fresh->student_count_actual,
            'updated_by' => $user->id,
            'dispatch_request' => $this->presentDispatchRequest($fresh),
        ]);
    }

    public function submitRecurringStudentCount(
        SubmitRecurringDispatchRequestStudentCountRequest $request,
        DispatchRequest $dispatchRequest,
    ) {
        if ($dispatchRequest->trashed()) {
            abort(404);
        }

        $user = $request->user();
        $before = $dispatchRequest->toArray();

        $dispatchRequest->update([
            'student_count_submitted_at' => now(),
            'student_count_submitted_by' => $user->id,
            'locked_at' => $dispatchRequest->locked_at ?? now(),
        ]);

        app(AuditLogger::class)->log(
            actorId: $user->id,
            event: 'request.student_count_submitted',
            auditable: $dispatchRequest,
            before: $before,
            after: $dispatchRequest->fresh()->toArray(),
            metadata: [
                'student_count_actual' => $dispatchRequest->student_count_actual,
            ],
        );

        $fresh = $dispatchRequest->fresh();

        DispatchStaffNotificationRecipients::notifyRecurringStudentCountSubmitted($fresh);

        return $this->ok([
            'dispatch_request' => $this->presentDispatchRequest($fresh),
        ]);
    }

    public function applyPricingHints(ApplyDispatchRequestPricingHintsRequest $request, DispatchRequest $dispatchRequest)
    {
        $this->authorize('view', $dispatchRequest);

        if ($dispatchRequest->trashed()) {
            abort(404);
        }

        $data = $request->validated();
        $snap = is_array($dispatchRequest->wizard_snapshot) ? $dispatchRequest->wizard_snapshot : [];
        $form = is_array($snap['form'] ?? null) ? $snap['form'] : [];

        if (array_key_exists('estimated_distance_km', $data) && $data['estimated_distance_km'] !== null) {
            $form['estimated_distance_km'] = $data['estimated_distance_km'];
        }
        if (array_key_exists('reference_unit_price', $data) && $data['reference_unit_price'] !== null) {
            $form['reference_unit_price'] = $data['reference_unit_price'];
        }
        if (! empty($data['pricing_source'])) {
            $form['pricing_source'] = $data['pricing_source'];
        }
        if (! empty($data['pricing_row_id'])) {
            $form['pricing_row_id'] = (int) $data['pricing_row_id'];
        }
        if (! empty($data['vehicle_hint'])) {
            $form['pricing_vehicle_hint'] = $data['vehicle_hint'];
        }

        $snap['form'] = $form;
        $dispatchRequest->update(['wizard_snapshot' => $snap]);
        $dispatchRequest->loadMissing('priceFiller:id,name,email');

        return $this->ok($this->presentDispatchRequest($dispatchRequest->fresh()));
    }

    public function patchWizard(UpdateDispatchRequestWizardRequest $request, DispatchRequest $dispatchRequest)
    {
        if ($dispatchRequest->trashed()) {
            abort(404);
        }

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

        $requesterId = $dispatchRequest->requester_id;
        if (! empty($data['requester_id']) && ($user->hasRole('dispatcher') || $user->hasRole('admin'))) {
            $requesterId = (int) $data['requester_id'];
        }

        $urgentReasonTrim = isset($data['urgent_reason']) ? trim((string) $data['urgent_reason']) : '';

        $before = $dispatchRequest->toArray();

        $dispatchRequest->update([
            'trip_type' => $data['trip_type'],
            'origin' => $data['origin'] ?? null,
            'destination' => $data['destination'] ?? null,
            'depart_at' => $departAt,
            'arrive_by' => isset($data['arrive_by']) && $data['arrive_by'] !== null && $data['arrive_by'] !== ''
                ? Carbon::parse($data['arrive_by'])
                : null,
            'passenger_count' => $data['passenger_count'] ?? null,
            'notes' => $data['notes'] ?? null,
            'wizard_snapshot' => $data['wizard_snapshot'] ?? null,
            'source_channel' => $data['source_channel'] ?? ($dispatchRequest->source_channel ?: 'portal'),
            'is_urgent' => $finalUrgent,
            'urgent_reason' => $finalUrgent ? ($urgentReasonTrim !== '' ? $urgentReasonTrim : null) : null,
            'urgent_trigger' => $urgentTrigger,
            'requester_id' => $requesterId,
        ]);

        app(AuditLogger::class)->log(
            actorId: $user->id,
            event: 'request.update_wizard',
            auditable: $dispatchRequest,
            before: $before,
            after: $dispatchRequest->fresh()->toArray(),
        );

        return $this->ok($this->presentDispatchRequest($dispatchRequest->fresh()));
    }

    public function clone(CloneDispatchRequestRequest $request, DispatchRequest $dispatchRequest)
    {
        if ($dispatchRequest->trashed()) {
            abort(404);
        }

        $this->authorize('view', $dispatchRequest);

        $user = $request->user();

        $departAt = $dispatchRequest->depart_at instanceof Carbon
            ? $dispatchRequest->depart_at->copy()
            : Carbon::parse((string) $dispatchRequest->depart_at);

        $clientUrgent = (bool) $dispatchRequest->is_urgent;
        [$finalUrgent, $urgentTrigger] = DispatchRequest::resolveUrgentTrigger(
            (string) $dispatchRequest->trip_type,
            $departAt,
            $clientUrgent,
        );

        $new = $dispatchRequest->replicate();

        $new->dispatch_request_template_id = null;
        $new->cloned_from_id = $dispatchRequest->id;
        $new->student_count_actual = null;
        $new->locked_at = null;
        $new->student_count_submitted_at = null;
        $new->student_count_submitted_by = null;
        $new->status = 'pending';
        $new->service_price = null;
        $new->price_filled_by = null;
        $new->price_filled_at = null;
        $new->approved_by = null;
        $new->rejection_reason = null;
        $new->paper_status = 'pending';
        $new->paper_received_at = null;
        $new->paper_reference = null;
        $new->is_urgent = $finalUrgent;
        $new->urgent_trigger = $urgentTrigger;
        $new->urgent_reason = $finalUrgent ? $dispatchRequest->urgent_reason : null;
        $new->source_channel = $dispatchRequest->source_channel ?: 'portal';

        $new->save();

        app(AuditLogger::class)->log(
            actorId: $user->id,
            event: 'request.clone',
            auditable: $new,
            before: null,
            after: $new->fresh()?->toArray(),
            metadata: ['from_dispatch_request_id' => $dispatchRequest->id],
        );

        return $this->created($this->presentDispatchRequest($new->fresh()));
    }

    private function scheduleRequesterDeptDecisionNotification(int $dispatchRequestId, string $decision): void
    {
        if (! in_array($decision, ['approve', 'reject'], true)) {
            return;
        }

        DB::afterCommit(function () use ($dispatchRequestId, $decision): void {
            $dispatchRequest = DispatchRequest::query()->find($dispatchRequestId);
            if (! $dispatchRequest) {
                return;
            }

            $dispatchRequest->loadMissing('requester');
            $requester = $dispatchRequest->requester;
            if (! $requester instanceof User) {
                return;
            }

            Notification::send(
                $requester,
                new DeptHeadDecisionNotification($dispatchRequestId, $decision),
            );

            if ($decision === 'approve') {
                $hasSignedPaper = $dispatchRequest->attachments()
                    ->where('kind', 'signed_paper')
                    ->exists();
                if (! $hasSignedPaper) {
                    Notification::send(
                        $requester,
                        new SignedPaperUploadReminderNotification($dispatchRequestId),
                    );
                }
            }
        });
    }
}
