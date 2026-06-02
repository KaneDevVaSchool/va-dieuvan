<?php

namespace App\Http\Controllers\Api\SignedDocuments;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Api\Concerns\PresentsSignedDocuments;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SignedDocuments\RunSignedDocumentOcrRequest;
use App\Http\Requests\Api\SignedDocuments\ShowSignedDocumentsRequest;
use App\Http\Requests\Api\SignedDocuments\StaffUploadSignedDocumentRequest;
use App\Http\Requests\Api\SignedDocuments\VerifySignedDocumentRequest;
use App\Http\Resources\SignedDocumentVersionResource;
use App\Jobs\ProcessSignedDocumentPipelineJob;
use App\Models\DispatchRequest;
use App\Models\SignedDocumentVersion;
use App\Services\Auditing\AuditLogger;
use App\Services\SignedDocuments\SignedDocumentPipelineService;
use App\Services\SignedDocuments\SignedDocumentUploadService;
use App\Services\SignedDocuments\SignedDocumentVerificationService;

class SignedDocumentController extends Controller
{
    use ApiResponses;
    use PresentsSignedDocuments;

    public function index(ShowSignedDocumentsRequest $request, DispatchRequest $dispatchRequest): \Illuminate\Http\JsonResponse
    {
        if ($dispatchRequest->trashed()) {
            abort(404);
        }

        $includeHistory = $request->boolean('include_history', true);

        $dispatchRequest->loadMissing([
            'currentSignedVersion.attachment',
            'currentSignedVersion.uploader',
        ]);

        if ($includeHistory) {
            $dispatchRequest->load([
                'signedDocumentVersions' => fn ($q) => $q->with(['attachment', 'uploader'])->orderByDesc('version_no'),
            ]);
        }

        return $this->ok($this->presentSignedDocumentBlock($dispatchRequest, $includeHistory));
    }

    public function store(
        StaffUploadSignedDocumentRequest $request,
        DispatchRequest $dispatchRequest,
        SignedDocumentUploadService $uploadService,
    ): \Illuminate\Http\JsonResponse {
        $result = $uploadService->upload(
            $dispatchRequest,
            $request->file('file'),
            $request->user(),
            'staff',
        );

        return $this->created([
            'version' => (new SignedDocumentVersionResource($result['version']))->resolve(),
            'attachment' => $result['attachment']->toArray(),
        ]);
    }

    public function runOcr(
        RunSignedDocumentOcrRequest $request,
        SignedDocumentVersion $signedDocumentVersion,
        SignedDocumentPipelineService $pipeline,
        AuditLogger $auditLogger,
    ): \Illuminate\Http\JsonResponse {
        $pipeline->requeue($signedDocumentVersion);

        if (config('dispatch.signed_document_use_queue', true)) {
            ProcessSignedDocumentPipelineJob::dispatch($signedDocumentVersion->id)
                ->onQueue(config('dispatch.document_processing_queue', 'document-processing'));
        } else {
            $pipeline->run($signedDocumentVersion->id);
        }

        $auditLogger->log(
            actorId: $request->user()?->id,
            event: 'signed_document.ocr_queued',
            auditable: $signedDocumentVersion,
            before: null,
            after: $signedDocumentVersion->fresh()->toArray(),
        );

        $fresh = $signedDocumentVersion->fresh(['attachment', 'uploader']);

        return $this->ok([
            ...(new SignedDocumentVersionResource($fresh))->resolve(),
            'ocr_status' => $fresh->ocr_status,
        ]);
    }

    public function verify(
        VerifySignedDocumentRequest $request,
        SignedDocumentVersion $signedDocumentVersion,
        SignedDocumentVerificationService $verificationService,
    ): \Illuminate\Http\JsonResponse {
        $data = $request->validated();
        $version = $verificationService->manualVerify(
            $signedDocumentVersion,
            $request->user(),
            $data['decision'],
            $data['note'] ?? null,
        );

        return $this->ok(new SignedDocumentVersionResource($version));
    }
}
