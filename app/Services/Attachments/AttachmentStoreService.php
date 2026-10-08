<?php

namespace App\Services\Attachments;

use App\Models\Attachment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Lưu file đính kèm cho checklist / hồ sơ chi phí: disk private + cột file_binary
 * (giống kho chứng từ) — xem/tải qua GET /attachments/{id}/download.
 */
class AttachmentStoreService
{
    public const DISK = 'local';

    /** Ảnh / PDF / scan văn phòng — chặn .html/.svg/.php… */
    public const FILE_MIMES_RULE = 'mimes:pdf,jpg,jpeg,png,webp,heic,heif';

    public const MAX_KB = 15360;

    public function store(Model $attachable, UploadedFile $file, ?int $actorId, string $kind, string $folder): Attachment
    {
        $path = Storage::disk(self::DISK)->putFileAs(
            "attachments/{$folder}/{$attachable->getKey()}",
            $file,
            $file->hashName(),
        );

        return Attachment::query()->create([
            'uploaded_by' => $actorId,
            'attachable_type' => $attachable->getMorphClass(),
            'attachable_id' => $attachable->getKey(),
            'kind' => $kind,
            'disk' => self::DISK,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'size_bytes' => $file->getSize(),
            'mime_type' => $file->getClientMimeType(),
            'file_binary' => Attachment::bytesFromUpload($file),
        ]);
    }

    public function delete(Attachment $attachment): void
    {
        if ($attachment->path) {
            Storage::disk($attachment->disk ?: self::DISK)->delete($attachment->path);
        }
        $attachment->delete();
    }
}
