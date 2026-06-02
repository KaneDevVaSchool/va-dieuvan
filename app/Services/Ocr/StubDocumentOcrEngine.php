<?php

namespace App\Services\Ocr;

use App\Models\Attachment;

class StubDocumentOcrEngine implements DocumentOcrEngine
{
    public function extract(Attachment $attachment): array
    {
        $hint = $attachment->original_name ?? 'file';
        $lines = [
            'Kết quả OCR (demo / stub engine)',
            'File: '.$hint,
            '---',
            'Gợi ý nội dung: kiểm tra số phiếu, ngày, chữ ký thủ công.',
            'Tích hợp thật: gửi ảnh tới API OCR, map field → paper_reference / notes.',
        ];

        return [
            'text' => implode("\n", $lines),
            'meta' => [
                'engine' => 'stub',
                'version' => 1,
                'mime_type' => $attachment->mime_type,
            ],
        ];
    }
}
