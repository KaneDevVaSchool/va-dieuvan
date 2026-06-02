<?php

namespace App\Services\SignedDocuments;

use App\Jobs\ProcessSignedDocumentPipelineJob;
use App\Models\Attachment;
use App\Models\DispatchRequest;
use App\Models\SignedDocumentVersion;
use App\Models\User;
use App\Services\Auditing\AuditLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SignedDocumentUploadService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly SignedDocumentPipelineService $pipelineService,
    ) {}

    /**
     * @return array{version: SignedDocumentVersion, attachment: Attachment}
     */
    public function upload(
        DispatchRequest $dispatchRequest,
        UploadedFile $file,
        ?User $user,
        string $source = 'portal',
    ): array {
        if ($dispatchRequest->trashed()) {
            abort(404);
        }

        if ($dispatchRequest->status !== 'approved') {
            throw ValidationException::withMessages([
                'file' => ['Chỉ upload bản đã ký khi phiếu đã duyệt.'],
            ]);
        }

        $maxKb = max(1, (int) config('dispatch.signed_upload.max_mb', 10)) * 1024;
        if ($file->getSize() > $maxKb * 1024) {
            throw ValidationException::withMessages([
                'file' => ['File vượt quá dung lượng cho phép.'],
            ]);
        }

        $allowed = config('dispatch.signed_upload.allowed_mimes', []);
        $mime = (string) $file->getClientMimeType();
        if ($allowed !== [] && ! in_array($mime, $allowed, true)) {
            throw ValidationException::withMessages([
                'file' => ['Định dạng file không được hỗ trợ.'],
            ]);
        }

        $disk = 'public';
        $hash = hash_file('sha256', $file->getRealPath() ?: '');

        return DB::transaction(function () use ($dispatchRequest, $file, $user, $source, $disk, $hash) {
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
                'sha256' => $hash ?: null,
                'file_binary' => Attachment::bytesFromUpload($file),
            ]);

            SignedDocumentVersion::query()
                ->where('dispatch_request_id', $dispatchRequest->getKey())
                ->where('is_current', true)
                ->update(['is_current' => false]);

            $nextVersion = (int) SignedDocumentVersion::query()
                ->where('dispatch_request_id', $dispatchRequest->getKey())
                ->max('version_no') + 1;

            $version = SignedDocumentVersion::query()->create([
                'dispatch_request_id' => $dispatchRequest->getKey(),
                'attachment_id' => $attachment->getKey(),
                'version_no' => max(1, $nextVersion),
                'is_current' => true,
                'uploaded_by' => $user?->id,
                'uploaded_at' => now(),
                'ocr_status' => 'queued',
                'verification_status' => 'pending',
            ]);

            $attachment->forceFill(['signed_document_version_id' => $version->id])->save();

            $dispatchRequest->forceFill([
                'signing_workflow_status' => 'signed_uploaded',
            ])->saveQuietly();

            SignedDocumentDispatchSync::syncFromVersion($version);

            $this->auditLogger->log(
                actorId: $user?->id,
                event: 'signed_document.upload',
                auditable: $version,
                before: null,
                after: $version->toArray(),
                metadata: [
                    'source' => $source,
                    'attachment_id' => $attachment->id,
                    'version_no' => $version->version_no,
                ],
            );

            $this->dispatchPipeline($version);

            return [
                'version' => $version->fresh(['attachment', 'uploader']),
                'attachment' => $attachment->fresh(),
            ];
        });
    }

    public function dispatchPipeline(SignedDocumentVersion $version): void
    {
        if (config('dispatch.signed_document_use_queue', true)) {
            ProcessSignedDocumentPipelineJob::dispatch($version->id)
                ->onQueue(config('dispatch.document_processing_queue', 'document-processing'));

            return;
        }

        $this->pipelineService->run($version->id);
    }
}
