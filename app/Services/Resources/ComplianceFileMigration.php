<?php

namespace App\Services\Resources;

use App\Models\Attachment;
use App\Models\DriverComplianceDocument;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/** Chuyển từng file; không bao giờ xóa nguồn. Command khóa bản ghi khi cập nhật metadata. */
class ComplianceFileMigration
{
    protected function folders(): string
    {
        return '(?:vehicle|driver)_compliance_documents';
    }

    protected function supports(Attachment $attachment): bool
    {
        return $attachment->isComplianceDocument();
    }

    protected function folder(Attachment $attachment): string
    {
        return in_array($attachment->attachable_type, [DriverComplianceDocument::class, 'driver_compliance_document'], true)
            ? 'driver_compliance_documents' : 'vehicle_compliance_documents';
    }

    public function validate(Attachment $attachment, string $bytes): void
    {
        if ($bytes === '' || ($attachment->size_bytes !== null && strlen($bytes) !== (int) $attachment->size_bytes)) {
            throw new RuntimeException('Nội dung rỗng hoặc dung lượng không khớp metadata.');
        }
        if ($attachment->sha256 && ! hash_equals($attachment->sha256, hash('sha256', $bytes))) {
            throw new RuntimeException('SHA-256 không khớp metadata.');
        }
    }

    /** Đọc nguồn hợp lệ; không chọn file sai khi có metadata kiểm chứng. */
    public function source(Attachment $attachment): string
    {
        $binary = $attachment->file_binary;
        if (is_string($binary) && $binary !== '') {
            try {
                $this->validate($attachment, $binary);

                return $binary;
            } catch (RuntimeException) {
                // Có thể binary cũ bị cắt; thử bản file trên disk.
            }
        }

        $raw = str_replace('\\', '/', trim((string) $attachment->path));
        if (preg_match('#^https?://#i', $raw)) {
            $raw = (string) parse_url($raw, PHP_URL_PATH);
        }
        // Chỉ nhận đường dẫn tương đối thuộc thư mục chứng từ, kể cả URL/absolute path cũ.
        if (! preg_match('#(?:^|/)(attachments/'.$this->folders().'/[^\r\n]+)$#', $raw, $match)) {
            throw new RuntimeException('Không có nguồn binary hợp lệ hoặc đường dẫn chứng từ được hỗ trợ.');
        }
        $path = $match[1];
        if (in_array('..', explode('/', $path), true)) {
            throw new RuntimeException('Đường dẫn chứa thành phần không hợp lệ.');
        }
        $disks = array_unique(array_filter([
            $attachment->disk, config('filesystems.default'), 'local', 'public',
        ]));
        foreach ($disks as $disk) {
            try {
                $bytes = Storage::disk($disk)->get($path);
                if (is_string($bytes)) {
                    $this->validate($attachment, $bytes);

                    return $bytes;
                }
            } catch (\Throwable) {
                // Thử nguồn cũ tiếp theo; không thay đổi nguồn và không làm mất binary.
            }
        }

        throw new RuntimeException('Không tìm thấy nguồn khớp dung lượng/SHA-256; giữ nguyên bản ghi.');
    }

    /** Kiểm tra chính xác disk/path hiện được ghi trong DB, không fallback sang binary. */
    public function verify(Attachment $attachment): string
    {
        if ($attachment->disk !== ComplianceDocumentService::fileDisk() || ! $attachment->path || ! $attachment->sha256) {
            throw new RuntimeException('Chưa chuyển về disk đích hoặc chưa có đường dẫn/SHA-256.');
        }
        $bytes = Storage::disk($attachment->disk)->get($attachment->path);
        if (! is_string($bytes)) {
            throw new RuntimeException('Không đọc được file đích.');
        }
        $this->validate($attachment, $bytes);

        return $bytes;
    }

    public function migrate(Attachment $attachment, bool $pruneBinary): void
    {
        if (! $this->supports($attachment)) {
            throw new RuntimeException('Không phải chứng từ xe/tài xế.');
        }
        $disk = ComplianceDocumentService::fileDisk();
        // Prune là bước riêng: chỉ xóa binary sau khi file đích đã được ghi và xác minh.
        if ($pruneBinary) {
            $this->verify($attachment);
            $attachment->update(['file_binary' => null]);

            return;
        }

        $bytes = $this->source($attachment);
        $sha = hash('sha256', $bytes);
        $folder = $this->folder($attachment);
        $path = "attachments/{$folder}/{$attachment->attachable_id}/migrated-{$attachment->id}-{$sha}";

        // File đã ở đích và còn nguyên thì giữ nguyên path, giúp chạy lại không sinh bản sao.
        if ($attachment->disk === $disk && $attachment->path) {
            try {
                $existing = Storage::disk($disk)->get($attachment->path);
                if (is_string($existing) && hash_equals($sha, hash('sha256', $existing))) {
                    $path = $attachment->path;
                }
            } catch (\Throwable) {
                // Khôi phục từ binary hoặc nguồn cũ đã xác minh.
            }
        }
        if (Storage::disk($disk)->exists($path)) {
            $existing = Storage::disk($disk)->get($path);
            if (! is_string($existing) || ! hash_equals($sha, hash('sha256', $existing))) {
                throw new RuntimeException('File đích đã tồn tại nhưng khác nội dung; không ghi đè.');
            }
            Storage::disk($disk)->setVisibility($path, 'private');
        } elseif (! Storage::disk($disk)->put($path, $bytes, ['visibility' => 'private'])) {
            throw new RuntimeException('Không ghi được file đích.');
        }
        $saved = Storage::disk($disk)->get($path);
        if (! is_string($saved) || strlen($saved) !== strlen($bytes) || ! hash_equals($sha, hash('sha256', $saved))) {
            throw new RuntimeException('File ghi xong không khớp nguồn; giữ nguyên metadata/binary.');
        }

        $attachment->update(['disk' => $disk, 'path' => $path, 'size_bytes' => strlen($bytes), 'sha256' => $sha]);
    }
}
