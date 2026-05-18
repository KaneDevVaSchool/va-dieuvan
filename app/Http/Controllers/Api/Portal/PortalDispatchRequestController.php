<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Api\Concerns\PresentsDispatchRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Portal\CreatePortalDispatchRequestRequest;
use App\Http\Requests\Api\Portal\IndexPortalDispatchRequestsRequest;
use App\Http\Requests\Api\Portal\PortalUploadSignedPaperRequest;
use App\Http\Requests\Api\Portal\ShowPortalDispatchRequestRequest;
use App\Http\Controllers\Api\Attachments\AttachmentController;
use App\Models\Attachment;
use App\Models\DispatchRequest;
use App\Models\Role;
use App\Models\User;
use App\Notifications\NewDispatchRequestNotification;
use App\Services\Auditing\AuditLogger;
use App\Support\Messages;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class PortalDispatchRequestController extends Controller
{
    use ApiResponses;
    use PresentsDispatchRequest;

    public function index(IndexPortalDispatchRequestsRequest $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        $perPage = isset($data['per_page']) ? max(1, min(50, (int) $data['per_page'])) : 10;

        $paginator = DispatchRequest::query()
            ->where('requester_id', $user->getKey())
            ->orderByDesc('depart_at')
            ->orderByDesc('id')
            ->with(['dispatchRequestTemplate.dispatchPackage'])
            ->paginate($perPage);

        $items = [];
        foreach ($paginator->items() as $dr) {
            /** @var DispatchRequest $dr */
            $items[] = $this->presentDispatchRequest($dr);
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
            'trip',
            'dispatchRequestTemplate.dispatchPackage',
            'attachments' => fn ($q) => $q->orderByDesc('id'),
        ]);

        return $this->ok($this->presentDispatchRequest($dispatchRequest));
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

    /**
     * Giống AttachmentController cho dispatch_request / signed_paper nhưng chỉ portal user đã được duyệt phiếu.
     */
    public function uploadSignedPaper(PortalUploadSignedPaperRequest $request, DispatchRequest $dispatchRequest): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $disk = 'public';
        /** @var UploadedFile $file */
        $file = $request->file('file');

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
            'kind' => 'signed_paper',
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'size_bytes' => $file->getSize(),
            'mime_type' => $file->getClientMimeType(),
            'file_binary' => Attachment::bytesFromUpload($file),
        ]);

        app(AuditLogger::class)->log(
            actorId: $user?->id,
            event: 'attachment.upload',
            auditable: $attachment,
            before: null,
            after: $attachment->toArray(),
            metadata: [
                'attachable_type' => 'dispatch_request',
                'attachable_id' => $dispatchRequest->getKey(),
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
}
