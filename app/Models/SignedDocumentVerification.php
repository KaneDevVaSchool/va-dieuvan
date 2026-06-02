<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SignedDocumentVerification extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'signed_document_version_id',
        'actor_id',
        'action',
        'before_status',
        'after_status',
        'payload',
        'created_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'created_at' => 'datetime',
    ];

    public function signedDocumentVersion(): BelongsTo
    {
        return $this->belongsTo(SignedDocumentVersion::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
