<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Api\Concerns\PresentsDispatchRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Portal\CreatePortalDispatchRequestRequest;
use App\Http\Requests\Api\Portal\IndexPortalDispatchRequestsRequest;
use App\Http\Requests\Api\Portal\PatchPortalRecurringDispatchInstanceRequest;
use App\Http\Requests\Api\Portal\PortalPatchSigningWorkflowRequest;
use App\Http\Requests\Api\Portal\PortalUploadProposalBasisRequest;
use App\Http\Requests\Api\Portal\PortalUploadSignedPaperRequest;
use App\Http\Requests\Api\Portal\ShowPortalDispatchRequestRequest;
use App\Http\Requests\Api\Portal\SubmitPortalRecurringDispatchInstanceRequest;
use App\Http\Requests\Api\Portal\SummaryPortalDispatchRequestsRequest;
use App\Services\Notifications\DispatchStaffNotificationRecipients;
use App\Http\Controllers\Api\Attachments\AttachmentController;
use App\Models\Attachment;
use App\Models\DispatchRequest;
use App\Models\User;
use App\Notifications\NewDispatchRequestNotification;
use App\Services\Auditing\AuditLogger;
use App\Http\Controllers\Api\SignedDocuments\SignedDocumentController;
use App\Http\Resources\SignedDocumentVersionResource;
use App\Services\RecurringDispatch\PortalRecurringBm03GroupSyncService;
use App\Services\SignedDocuments\SignedDocumentUploadService;
use App\Services\SignedDocuments\SigningWorkflowService;
use App\Support\Messages;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\Eloquent\Builder;

class PortalDispatchRequestController extends Controller
{
    use ApiResponses;
    use PresentsDispatchRequest;

    public function summary(SummaryPortalDispatchRequestsRequest $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        $base = DispatchRequest::query()->where('requester_id', $user->getKey());

        $pendingApproval = (clone $base)->whereIn('status', ['pending', 'price_filled'])->count();

        $processing = (clone $base)->where('status', 'approved')
            ->where(function ($q) {
                $q->whereDoesntHave('trip')
                    ->orWhereHas('trip', function ($tq) {
                        $tq->whereNotIn('status', ['completed', 'cancelled']);
                    });
            })->count();

        $startOfMonth = now()->startOfMonth();
        $completedThisMonth = (clone $base)->where('status', 'approved')
            ->whereHas('trip', function ($tq) use ($startOfMonth) {
                $tq->where('status', 'completed')
                    ->whereNotNull('completed_at')
                    ->where('completed_at', '>=', $startOfMonth);
            })->count();

        $rejected = (clone $base)->where('status', 'rejected')->count();

        $startPrevMonth = now()->subMonth()->startOfMonth();
        $endPrevMonth = now()->subMonth()->endOfMonth();
        $completedPrevMonth = (clone $base)->where('status', 'approved')
            ->whereHas('trip', function ($tq) use ($startPrevMonth, $endPrevMonth) {
                $tq->where('status', 'completed')
                    ->whereNotNull('completed_at')
                    ->whereBetween('completed_at', [$startPrevMonth, $endPrevMonth]);
            })->count();

        return $this->ok([
            'processing' => $processing,
            'pending' => $pendingApproval,
            'completed_this_month' => $completedThisMonth,
            'rejected' => $rejected,
            'trends' => [
                'completed_this_month' => $completedThisMonth - $completedPrevMonth,
            ],
        ]);
    }

    public function index(IndexPortalDispatchRequestsRequest $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        $perPage = isset($data['per_page']) ? max(1, min(500, (int) $data['per_page'])) : 10;

        $sort = isset($data['sort']) ? (string) $data['sort'] : 'depart_desc';

        $filter = isset($data['filter']) ? (string) $data['filter'] : 'all';

        $query = DispatchRequest::query()
            ->where('requester_id', $user->getKey())
            ->with(['dispatchRequestTemplate.dispatchPackage', 'trip:id,dispatch_request_id,status']);

        match ($filter) {
            'pending' => $query->whereIn('status', ['pending', 'price_filled']),
            'approved' => $query->where('status', 'approved'),
            'rejected' => $query->where('status', 'rejected'),
            'returned' => $query->where('status', 'rejected'),
            default => null,
        };

        if (isset($data['trip_type'])) {
            $query->where('trip_type', (string) $data['trip_type']);
        }

        if (array_key_exists('is_urgent', $data)) {
            $query->where('is_urgent', (bool) $data['is_urgent']);
        }

        if (isset($data['date_from'])) {
            $query->whereDate('depart_at', '>=', $data['date_from']);
        }

        if (isset($data['date_to'])) {
            $query->whereDate('depart_at', '<=', $data['date_to']);
        }

        if (! empty($data['extracurricular_only'])) {
            $query->extracurricularOnly();
        }

        $qRaw = isset($data['q']) ? trim((string) $data['q']) : '';
        if ($qRaw !== '') {
            $like = '%'.addcslashes($qRaw, '%_\\').'%';
            $query->where(function (Builder $b) use ($qRaw, $like) {
                if (ctype_digit($qRaw)) {
                    $b->where('id', (int) $qRaw);
                }
                $b->orWhere('origin', 'like', $like)
                    ->orWhere('destination', 'like', $like);
            });
        }

        match ($sort) {
            'depart_asc' => $query->orderBy('depart_at')->orderBy('id'),
            'created_desc' => $query->orderByDesc('created_at')->orderByDesc('id'),
            'created_asc' => $query->orderBy('created_at')->orderBy('id'),
            default => $query->orderByDesc('depart_at')->orderByDesc('id'),
        };

        $paginator = $query->paginate($perPage);

        $items = [];
        foreach ($paginator->items() as $dr) {
            /** @var DispatchRequest $dr */
            $items[] = $this->presentDispatchRequest($dr, true);
        }

        return $this->ok([
            'items' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(ShowPortalDispatchRequestRequest $request, DispatchRequest $dispatchRequest): \Illuminate\Http\JsonResponse
    {
        if ($dispatchRequest->trashed()) {
            abort(404);
        }

        $dispatchRequest->load([
            'requester:id,name,email,phone,department_id',
            'requester.department:id,name',
            'trip',
            'dispatchRequestTemplate.dispatchPackage',
            'attachments' => fn ($q) => $q->orderByDesc('id'),
            'currentSignedVersion.attachment',
            'currentSignedVersion.uploader',
        ]);

        return $this->ok($this->presentDispatchRequest($dispatchRequest, true));
    }

    /** Tải chứng từ gắn phiếu — portal user chỉ được file thuộc phiếu của mình */
    public function downloadAttachment(
        ShowPortalDispatchRequestRequest $request,
        DispatchRequest $dispatchRequest,
        Attachment $attachment
    ): \Symfony\Component\HttpFoundation\Response {
        if ($dispatchRequest->trashed()) {
            abort(404);
        }

        if (
            $attachment->attachable_type !== $dispatchRequest->getMorphClass()
            || (int) $attachment->attachable_id !== (int) $dispatchRequest->getKey()
        ) {
            abort(404);
        }

        return app(AttachmentController::class)->download($request, $attachment);
    }

    public function uploadSignedPaper(
        PortalUploadSignedPaperRequest $request,
        DispatchRequest $dispatchRequest,
        SignedDocumentUploadService $uploadService,
    ): \Illuminate\Http\JsonResponse {
        $result = $uploadService->upload(
            $dispatchRequest,
            $request->file('file'),
            $request->user(),
            'portal',
        );

        return $this->created([
            'version' => (new SignedDocumentVersionResource($result['version']))->resolve(),
            'attachment' => array_merge($result['attachment']->toArray(), [
                'url' => \Illuminate\Support\Facades\Storage::url($result['attachment']->path),
            ]),
            ...$this->presentSignedDocumentBlock($dispatchRequest->fresh(['currentSignedVersion.attachment', 'currentSignedVersion.uploader']), false),
        ]);
    }

    public function signedDocuments(
        \App\Http\Requests\Api\SignedDocuments\ShowSignedDocumentsRequest $request,
        DispatchRequest $dispatchRequest,
    ): \Illuminate\Http\JsonResponse {
        return app(SignedDocumentController::class)->index($request, $dispatchRequest);
    }

    public function patchSigningWorkflow(
        PortalPatchSigningWorkflowRequest $request,
        DispatchRequest $dispatchRequest,
        SigningWorkflowService $workflowService,
    ): \Illuminate\Http\JsonResponse {
        $data = $request->validated();
        $fresh = $workflowService->updateStatus($dispatchRequest, $request->user(), $data['status']);

        return $this->ok($this->presentDispatchRequest($fresh, true));
    }

    /**
     * Căn cứ đề xuất (BM.03 b.2) — người đề xuất, phiếu CLB định kỳ chưa chốt.
     */
    public function uploadProposalBasis(
        PortalUploadProposalBasisRequest $request,
        DispatchRequest $dispatchRequest,
    ): \Illuminate\Http\JsonResponse {
        $user = $request->user();
        $disk = 'public';
        /** @var UploadedFile $file */
        $file = $request->file('file');

        Attachment::query()
            ->where('attachable_type', $dispatchRequest->getMorphClass())
            ->where('attachable_id', $dispatchRequest->getKey())
            ->where('kind', 'proposal_basis')
            ->delete();

        $path = Storage::putFileAs(
            "attachments/dispatch_requests/{$dispatchRequest->getKey()}",
            $file,
            $file->hashName(),
            ['disk' => $disk],
        );

        $attachment = Attachment::create([
            'uploaded_by' => $user?->id,
            'attachable_type' => $dispatchRequest->getMorphClass(),
            'attachable_id' => $dispatchRequest->getKey(),
            'kind' => 'proposal_basis',
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'size_bytes' => $file->getSize(),
            'mime_type' => $file->getClientMimeType(),
            'file_binary' => Attachment::bytesFromUpload($file),
        ]);

        $snap = is_array($dispatchRequest->wizard_snapshot) ? $dispatchRequest->wizard_snapshot : [];
        $form = is_array($snap['form'] ?? null) ? $snap['form'] : [];
        $form['basisFileName'] = $file->getClientOriginalName();
        $form['basis_ref'] = '';
        $snap['form'] = $form;
        $dispatchRequest->forceFill(['wizard_snapshot' => $snap])->saveQuietly();

        app(PortalRecurringBm03GroupSyncService::class)
            ->syncSharedBm03FromInstance($dispatchRequest->fresh());

        app(AuditLogger::class)->log(
            actorId: $user?->id,
            event: 'attachment.upload',
            auditable: $attachment,
            before: null,
            after: $attachment->toArray(),
            metadata: [
                'attachable_type' => 'dispatch_request',
                'attachable_id' => $dispatchRequest->getKey(),
                'kind' => 'proposal_basis',
                'source' => 'portal',
            ],
        );

        return $this->created([
            ...$attachment->toArray(),
            'url' => Storage::url($path),
        ]);
    }

    public function store(CreatePortalDispatchRequestRequest $request)
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

        $urgentReasonTrim = isset($data['urgent_reason']) ? trim((string) $data['urgent_reason']) : '';

        $dispatchRequest = DispatchRequest::create([
            ...$data,
            'requester_id' => $user->id,
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

        $recipients = DispatchStaffNotificationRecipients::users();
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

        return $this->created($this->presentDispatchRequest($dispatchRequest, true));
    }

    public function patchRecurringInstance(
        PatchPortalRecurringDispatchInstanceRequest $request,
        DispatchRequest $dispatchRequest,
    ): \Illuminate\Http\JsonResponse {
        $data = $request->validated();
        $user = $request->user();
        $before = $dispatchRequest->toArray();

        $updates = [];
        if (array_key_exists('student_count_actual', $data)) {
            $updates['student_count_actual'] = $data['student_count_actual'];
            if ($dispatchRequest->dispatch_request_template_id !== null) {
                $updates['passenger_count'] = $data['student_count_actual'];
            }
        }
        if (! empty($data['depart_at'])) {
            $updates['depart_at'] = Carbon::parse($data['depart_at']);
        }
        if (array_key_exists('arrive_by', $data)) {
            $updates['arrive_by'] = ! empty($data['arrive_by']) ? Carbon::parse($data['arrive_by']) : null;
        }
        if (array_key_exists('origin', $data)) {
            $updates['origin'] = $data['origin'];
        }
        if (array_key_exists('destination', $data)) {
            $updates['destination'] = $data['destination'];
        }
        if (array_key_exists('notes', $data)) {
            $updates['notes'] = $data['notes'];
        }
        if (! empty($data['wizard_snapshot']) && is_array($data['wizard_snapshot'])) {
            $merged = is_array($dispatchRequest->wizard_snapshot)
                ? $dispatchRequest->wizard_snapshot
                : [];
            $updates['wizard_snapshot'] = array_replace_recursive($merged, $data['wizard_snapshot']);
        }

        if ($updates !== []) {
            $dispatchRequest->update($updates);
        }

        $fresh = $dispatchRequest->fresh();
        $shouldSyncBm03Group = isset($updates['wizard_snapshot'])
            || isset($updates['origin'])
            || isset($updates['destination']);
        if ($shouldSyncBm03Group && $fresh !== null) {
            app(PortalRecurringBm03GroupSyncService::class)
                ->syncSharedBm03FromInstance($fresh);
        }

        app(AuditLogger::class)->log(
            actorId: $user->id,
            event: 'request.recurring_instance_updated',
            auditable: $dispatchRequest,
            before: $before,
            after: $dispatchRequest->fresh()->toArray(),
        );

        return $this->ok($this->presentDispatchRequest($dispatchRequest->fresh(), true));
    }

    public function submitRecurringInstance(
        SubmitPortalRecurringDispatchInstanceRequest $request,
        DispatchRequest $dispatchRequest,
    ): \Illuminate\Http\JsonResponse {
        $user = $request->user();
        $before = $dispatchRequest->toArray();

        app(PortalRecurringBm03GroupSyncService::class)
            ->stampProposedDateOnSubmit($dispatchRequest);

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
                'channel' => 'portal',
            ],
        );

        $fresh = $dispatchRequest->fresh();

        DispatchStaffNotificationRecipients::notifyRecurringStudentCountSubmitted($fresh);

        return $this->ok([
            'dispatch_request' => $this->presentDispatchRequest($fresh, true),
        ]);
    }
}
