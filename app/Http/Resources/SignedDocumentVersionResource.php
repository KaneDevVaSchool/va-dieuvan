<?php

namespace App\Http\Resources;

use App\Models\SignedDocumentVersion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SignedDocumentVersion */
class SignedDocumentVersionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $attachment = $this->whenLoaded('attachment', fn () => $this->attachment);

        return [
            'id' => $this->id,
            'dispatch_request_id' => $this->dispatch_request_id,
            'attachment_id' => $this->attachment_id,
            'version_no' => $this->version_no,
            'is_current' => $this->is_current,
            'uploaded_at' => $this->uploaded_at?->toIso8601String(),
            'uploaded_by' => $this->uploaded_by,
            'uploader' => $this->whenLoaded('uploader', fn () => $this->uploader ? [
                'id' => $this->uploader->id,
                'name' => $this->uploader->name,
            ] : null),
            'ocr_status' => $this->ocr_status,
            'ocr_started_at' => $this->ocr_started_at?->toIso8601String(),
            'ocr_completed_at' => $this->ocr_completed_at?->toIso8601String(),
            'ocr_error' => $this->ocr_error,
            'signature_detected' => $this->signature_detected,
            'signature_score' => $this->signature_score !== null ? (float) $this->signature_score : null,
            'signature_regions' => $this->signature_regions,
            'signature_verified' => $this->signature_verified,
            'verification_status' => $this->verification_status,
            'verified_at' => $this->verified_at?->toIso8601String(),
            'verified_by' => $this->verified_by,
            'verification_note' => $this->verification_note,
            'attachment' => $attachment ? [
                'id' => $attachment->id,
                'kind' => $attachment->kind,
                'original_name' => $attachment->original_name,
                'mime_type' => $attachment->mime_type,
                'size_bytes' => $attachment->size_bytes,
                'url' => $attachment->url,
                'ocr_text' => $attachment->ocr_text,
                'ocr_meta' => $attachment->ocr_meta,
                'ocr_processed_at' => $attachment->ocr_processed_at?->toIso8601String(),
            ] : null,
        ];
    }
}
