<?php

namespace App\Services\SignedDocuments;

use App\Models\SignedDocumentVerification;
use App\Models\SignedDocumentVersion;
use App\Services\Auditing\AuditLogger;
use App\Services\Ocr\DocumentOcrEngine;
use App\Services\Signature\SignatureDetectionService;
use Illuminate\Support\Facades\DB;

class SignedDocumentPipelineService
{
    public function __construct(
        private readonly DocumentOcrEngine $ocrEngine,
        private readonly SignatureDetectionService $signatureDetection,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function run(int $signedDocumentVersionId): void
    {
        $version = SignedDocumentVersion::query()
            ->with(['attachment', 'dispatchRequest'])
            ->find($signedDocumentVersionId);

        if ($version === null || $version->attachment === null) {
            return;
        }

        $beforeStatus = $version->verification_status;

        $version->forceFill([
            'ocr_status' => 'processing',
            'verification_status' => 'processing',
            'ocr_started_at' => now(),
            'ocr_error' => null,
        ])->save();

        try {
            $attachment = $version->attachment->fresh();
            $ocr = $this->ocrEngine->extract($attachment);
            $attachment->forceFill([
                'ocr_text' => $ocr['text'],
                'ocr_meta' => $ocr['meta'],
                'ocr_processed_at' => now(),
            ])->save();

            $sig = $this->signatureDetection->analyze($attachment);

            $signatureVerified = $sig['verification_status'] === 'auto_pass';

            $version->forceFill([
                'ocr_status' => 'completed',
                'ocr_completed_at' => now(),
                'signature_detected' => $sig['detected'],
                'signature_score' => $sig['score'],
                'signature_regions' => $sig['regions'],
                'signature_verified' => $signatureVerified,
                'verification_status' => $sig['verification_status'],
            ])->save();

            SignedDocumentVerification::query()->create([
                'signed_document_version_id' => $version->id,
                'actor_id' => null,
                'action' => 'ocr_completed',
                'before_status' => $beforeStatus,
                'after_status' => $sig['verification_status'],
                'payload' => [
                    'ocr_engine' => $ocr['meta']['engine'] ?? null,
                    'signature_score' => $sig['score'],
                ],
                'created_at' => now(),
            ]);

            SignedDocumentVerification::query()->create([
                'signed_document_version_id' => $version->id,
                'actor_id' => null,
                'action' => 'auto_decision',
                'before_status' => 'processing',
                'after_status' => $sig['verification_status'],
                'payload' => $sig,
                'created_at' => now(),
            ]);

            $this->auditLogger->log(
                actorId: null,
                event: 'signed_document.ocr_completed',
                auditable: $version,
                before: ['verification_status' => $beforeStatus],
                after: $version->fresh()->toArray(),
            );

            $this->auditLogger->log(
                actorId: null,
                event: 'signed_document.auto_decision',
                auditable: $version,
                before: null,
                after: ['verification_status' => $sig['verification_status']],
                metadata: ['score' => $sig['score']],
            );

            SignedDocumentDispatchSync::syncFromVersion($version->fresh());
        } catch (\Throwable $e) {
            $version->forceFill([
                'ocr_status' => 'failed',
                'ocr_completed_at' => now(),
                'ocr_error' => $e->getMessage(),
                'verification_status' => 'manual_review',
            ])->save();

            SignedDocumentDispatchSync::syncFromVersion($version->fresh());

            throw $e;
        }
    }

    public function requeue(SignedDocumentVersion $version): void
    {
        DB::transaction(function () use ($version) {
            $before = $version->verification_status;
            $version->forceFill([
                'ocr_status' => 'queued',
                'verification_status' => 'pending',
                'ocr_started_at' => null,
                'ocr_completed_at' => null,
                'ocr_error' => null,
            ])->save();

            SignedDocumentVerification::query()->create([
                'signed_document_version_id' => $version->id,
                'actor_id' => null,
                'action' => 're_run_ocr',
                'before_status' => $before,
                'after_status' => 'pending',
                'payload' => null,
                'created_at' => now(),
            ]);
        });
    }
}
