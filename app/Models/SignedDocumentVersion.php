<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SignedDocumentVersion extends Model
{
    protected $fillable = [
        'dispatch_request_id',
        'attachment_id',
        'version_no',
        'is_current',
        'source_pdf_sha256',
        'uploaded_by',
        'uploaded_at',
        'ocr_status',
        'ocr_started_at',
        'ocr_completed_at',
        'ocr_error',
        'signature_detected',
        'signature_score',
        'signature_regions',
        'signature_verified',
        'verification_status',
        'verified_by',
        'verified_at',
        'verification_note',
    ];

    protected $casts = [
        'is_current' => 'boolean',
        'uploaded_at' => 'datetime',
        'ocr_started_at' => 'datetime',
        'ocr_completed_at' => 'datetime',
        'signature_detected' => 'boolean',
        'signature_score' => 'decimal:4',
        'signature_regions' => 'array',
        'signature_verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    public function dispatchRequest(): BelongsTo
    {
        return $this->belongsTo(DispatchRequest::class);
    }

    public function attachment(): BelongsTo
    {
        return $this->belongsTo(Attachment::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(SignedDocumentVerification::class);
    }
}
