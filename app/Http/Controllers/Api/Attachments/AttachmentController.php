<?php

namespace App\Http\Controllers\Api\Attachments;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Attachments\RunAttachmentOcrRequest;
use App\Http\Requests\Api\Attachments\UploadAttachmentRequest;
use App\Models\Attachment;
use App\Models\CargoShipment;
use App\Models\DispatchRequest;
use App\Models\Trip;
use App\Models\TripCost;
use App\Services\Auditing\AuditLogger;
use App\Services\Ocr\PaperOcrStubService;
use App\Support\FinancialDataLock;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    use ApiResponses;

    public function upload(UploadAttachmentRequest $request)
    {
        $data = $request->validated();

        $user = $request->user();

        [$attachable, $folder] = match ($data['attachable_type']) {
            'trip' => [Trip::findOrFail($data['attachable_id']), 'trips'],
            'cargo_shipment' => [CargoShipment::findOrFail($data['attachable_id']), 'cargo'],
            'trip_cost' => [TripCost::findOrFail($data['attachable_id']), 'costs'],
            'dispatch_request' => [DispatchRequest::findOrFail($data['attachable_id']), 'dispatch_requests'],
        };

        if ($attachable instanceof TripCost) {
            FinancialDataLock::assertTripCostAllowsNewAttachment($attachable);
        }

        $disk = 'public';
        /** @var UploadedFile $file */
        $file = $request->file('file');

        $path = Storage::putFileAs(
            "attachments/{$folder}/{$attachable->getKey()}",
            $file,
            $file->hashName(),
            ['disk' => $disk],
        );

        $attachment = Attachment::create([
            'uploaded_by' => $user?->id,
            'attachable_type' => $attachable->getMorphClass(),
            'attachable_id' => $attachable->getKey(),
            'kind' => $data['kind'] ?? null,
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'size_bytes' => $file->getSize(),
            'mime_type' => $file->getClientMimeType(),
        ]);

        app(AuditLogger::class)->log(
            actorId: $user?->id,
            event: 'attachment.upload',
            auditable: $attachment,
            before: null,
            after: $attachment->toArray(),
            metadata: [
                'attachable_type' => $data['attachable_type'],
                'attachable_id' => $data['attachable_id'],
            ],
        );

        return $this->created([
            ...$attachment->toArray(),
            'url' => Storage::url($path),
        ]);
    }

    public function runOcr(RunAttachmentOcrRequest $request, Attachment $attachment, PaperOcrStubService $ocr)
    {
        if ($attachment->kind !== 'paper_scan') {
            abort(422, 'Chỉ hỗ trợ OCR cho file loại paper_scan.');
        }

        $ocr->process($attachment->fresh());

        app(AuditLogger::class)->log(
            actorId: $request->user()?->id,
            event: 'attachment.ocr_stub',
            auditable: $attachment,
            before: null,
            after: $attachment->fresh()->toArray(),
        );

        return $this->ok($attachment->fresh());
    }
}
