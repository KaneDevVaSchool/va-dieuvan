<?php

namespace App\Services\Ocr;

use App\Models\Attachment;

interface DocumentOcrEngine
{
    /**
     * @return array{text: string, meta: array<string, mixed>}
     */
    public function extract(Attachment $attachment): array;
}
