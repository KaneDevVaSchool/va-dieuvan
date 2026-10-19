<?php

namespace App\Services\Attachments;

use App\Models\Attachment;
use App\Services\Resources\ComplianceDocumentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Lưu ảnh checklist / scan chi phí trên disk private; DB chỉ giữ metadata và SHA-256.
 * Xem/tải qua GET /attachments/{id}/download có kiểm tra quyền.
 */
class AttachmentStoreService
{
    /** Ảnh / PDF / scan văn phòng — chặn .html/.svg/.php… */
    public const FILE_MIMES_RULE = 'mimes:pdf,jpg,jpeg,png,webp,heic,heif';

    public const MAX_KB = 15360;

    public function store(Model $attachable, UploadedFile $file, ?int $actorId, string $kind, string $folder): Attachment
    {
        $disk = ComplianceDocumentService::fileDisk();
        $path = Storage::disk($disk)->putFileAs(
            "attachments/{$folder}/{$attachable->getKey()}",
            $file,
            $file->hashName(),
            ['visibility' => 'private'],
        );
        if (! is_string($path) || $path === '') {
            throw new \RuntimeException('Không ghi được file đính kèm lên kho private.');
        }
        try {
            return Attachment::query()->create([
                'uploaded_by' => $actorId,
                'attachable_type' => $attachable->getMorphClass(),
                'attachable_id' => $attachable->getKey(),
                'kind' => $kind,
                'disk' => $disk,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'size_bytes' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'sha256' => hash_file('sha256', $file->getRealPath()),
            ]);
        } catch (\Throwable $e) {
            Storage::disk($disk)->delete($path);
            throw $e;
        }
    }

    public function delete(Attachment $attachment): void
    {
        if ($attachment->path) {
            Storage::disk($attachment->disk ?: 'local')->delete($attachment->path);
        }
        $attachment->delete();
    }
}
