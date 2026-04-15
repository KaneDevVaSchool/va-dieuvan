<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    use HasFactory;

    protected $appends = ['url'];

    protected $hidden = [
        'file_binary',
    ];

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
        'file_binary',
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

    /**
     * Đọc bytes từ file upload để lưu vào cột file_binary (song song với disk).
     */
    public static function bytesFromUpload(UploadedFile $file): string
    {
        $real = $file->getRealPath();
        if ($real === false || ! is_readable($real)) {
            throw new \RuntimeException('Không đọc được file tạm upload.');
        }
        $contents = file_get_contents($real);
        if ($contents === false) {
            throw new \RuntimeException('Đọc nội dung file thất bại.');
        }

        return $contents;
    }
}
