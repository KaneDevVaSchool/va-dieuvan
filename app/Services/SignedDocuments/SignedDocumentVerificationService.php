<?php

namespace App\Services\SignedDocuments;

use App\Models\SignedDocumentVerification;
use App\Models\SignedDocumentVersion;
use App\Models\User;
use App\Services\Auditing\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SignedDocumentVerificationService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function manualVerify(
        SignedDocumentVersion $version,
        User $user,
        string $decision,
        ?string $note = null,
    ): SignedDocumentVersion {
        if (! in_array($decision, ['approve', 'reject'], true)) {
            throw ValidationException::withMessages(['decision' => ['Quyết định không hợp lệ.']]);
        }

        return DB::transaction(function () use ($version, $user, $decision, $note) {
            $before = $version->verification_status;
            $after = $decision === 'approve' ? 'verified' : 'rejected';

            $version->forceFill([
                'signature_verified' => $decision === 'approve',
                'verification_status' => $after,
                'verified_by' => $user->id,
                'verified_at' => now(),
                'verification_note' => $note,
            ])->save();

            SignedDocumentVerification::query()->create([
                'signed_document_version_id' => $version->id,
                'actor_id' => $user->id,
                'action' => $decision === 'approve' ? 'manual_verify' : 'manual_reject',
                'before_status' => $before,
                'after_status' => $after,
                'payload' => ['note' => $note],
                'created_at' => now(),
            ]);

            $this->auditLogger->log(
                actorId: $user->id,
                event: 'signed_document.manual_verify',
                auditable: $version,
                before: ['verification_status' => $before],
                after: $version->fresh()->toArray(),
                metadata: ['decision' => $decision],
            );

            SignedDocumentDispatchSync::syncFromVersion($version->fresh());

            return $version->fresh(['attachment', 'uploader', 'verifier']);
        });
    }
}
