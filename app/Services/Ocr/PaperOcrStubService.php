<?php

namespace App\Services\Ocr;

use App\Models\Attachment;

/**
 * Stub OCR — sẵn sàng thay bằng Google Vision / Azure Document Intelligence.
 */
class PaperOcrStubService
{
    public function __construct(
        private readonly DocumentOcrEngine $engine,
    ) {}

    public function process(Attachment $attachment): void
    {
        $result = $this->engine->extract($attachment);

        $attachment->forceFill([
            'ocr_text' => $result['text'],
            'ocr_meta' => $result['meta'],
            'ocr_processed_at' => now(),
        ])->save();
    }
}
