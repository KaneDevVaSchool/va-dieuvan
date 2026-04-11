<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    use HasFactory;

    protected $appends = ['url'];

    protected $fillable = [
        'uploaded_by',
        'attachable_type',
        'attachable_id',
        'kind',
        'disk',
        'path',
        'original_name',
        'size_bytes',
        'mime_type',
        'ocr_text',
        'ocr_meta',
        'ocr_processed_at',
    ];

    protected $casts = [
        'ocr_meta' => 'array',
        'ocr_processed_at' => 'datetime',
    ];

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getUrlAttribute(): ?string
    {
        if (! $this->path) {
            return null;
        }

        return Storage::disk($this->disk ?: 'public')->url($this->path);
    }
}
