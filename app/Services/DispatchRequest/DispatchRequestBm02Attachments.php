<?php

namespace App\Services\DispatchRequest;

use App\Models\Attachment;
use App\Models\DispatchRequest;
use App\Services\Auditing\AuditLogger;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DispatchRequestBm02Attachments
{
    public function __construct(
        private Bm02P2pFormGenerator $generator,
        private AuditLogger $auditLogger,
    ) {}

    /**
     * Gắn file Excel + PDF BM.02 vào yêu cầu (lưu DB + file_binary).
     */
    public function store(DispatchRequest $dispatchRequest, ?int $uploadedById, array $wizard): void
    {
        $binaries = $this->generator->generate($wizard);
        $slug = 'BM02-denghi-dieuvan-'.$dispatchRequest->id;

        $this->persistOne(
            $dispatchRequest,
            $uploadedById,
            $binaries['xlsx'],
            $slug.'.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'bm02_excel',
        );
        $this->persistOne(
            $dispatchRequest,
            $uploadedById,
            $binaries['pdf'],
            $slug.'.pdf',
            'application/pdf',
            'bm02_pdf',
        );
    }

    private function persistOne(
        DispatchRequest $dispatchRequest,
        ?int $uploadedById,
        string $bytes,
        string $originalName,
        string $mime,
        string $kind,
    ): void {
        $folder = 'attachments/dispatch_requests/'.$dispatchRequest->getKey();
        $name = Str::uuid()->toString().'_'.preg_replace('/[^a-zA-Z0-9._-]+/', '_', $originalName);
        $path = $folder.'/'.$name;

        Storage::disk('public')->put($path, $bytes);

        $attachment = Attachment::create([
            'uploaded_by' => $uploadedById,
            'attachable_type' => $dispatchRequest->getMorphClass(),
            'attachable_id' => $dispatchRequest->getKey(),
            'kind' => $kind,
            'disk' => 'public',
            'path' => $path,
            'original_name' => $originalName,
            'size_bytes' => strlen($bytes),
            'mime_type' => $mime,
            'file_binary' => $bytes,
        ]);

        $this->auditLogger->log(
            actorId: $uploadedById,
            event: 'attachment.upload',
            auditable: $attachment,
            before: null,
            after: $attachment->toArray(),
            metadata: [
                'attachable_type' => 'dispatch_request',
                'attachable_id' => $dispatchRequest->getKey(),
                'source' => 'bm02_generator',
            ],
        );
    }
}
