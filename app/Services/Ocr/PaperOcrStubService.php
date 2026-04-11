<?php

namespace App\Services\Ocr;

use App\Models\Attachment;

/**
 * Stub OCR — sẵn sàng thay bằng Google Vision / Azure Document Intelligence.
 */
class PaperOcrStubService
{
    public function process(Attachment $attachment): void
    {
        $hint = $attachment->original_name ?? 'file';
        $lines = [
            'Kết quả OCR (demo / stub engine)',
            'File: '.$hint,
            '---',
            'Gợi ý nội dung: kiểm tra số phiếu, ngày, chữ ký thủ công.',
            'Tích hợp thật: gửi ảnh tới API OCR, map field → paper_reference / notes.',
        ];

        $attachment->forceFill([
            'ocr_text' => implode("\n", $lines),
            'ocr_meta' => [
                'engine' => 'stub',
                'version' => 1,
                'mime_type' => $attachment->mime_type,
            ],
            'ocr_processed_at' => now(),
        ])->save();
    }
}
