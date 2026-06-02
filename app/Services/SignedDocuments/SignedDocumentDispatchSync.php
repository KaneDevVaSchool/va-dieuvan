<?php

namespace App\Services\SignedDocuments;

use App\Models\DispatchRequest;
use App\Models\SignedDocumentVersion;

class SignedDocumentDispatchSync
{
    public static function syncFromVersion(SignedDocumentVersion $version): void
    {
        $version->loadMissing('dispatchRequest');
        $dr = $version->dispatchRequest;
        if (! $dr instanceof DispatchRequest) {
            return;
        }

        if (! $version->is_current) {
            return;
        }

        $dr->forceFill([
            'current_signed_version_id' => $version->id,
            'signed_at' => $version->uploaded_at,
            'signed_by' => $version->uploaded_by,
            'signature_detected' => $version->signature_detected,
            'signature_verified' => $version->signature_verified,
            'verification_status' => $version->verification_status,
        ])->saveQuietly();
    }
}
