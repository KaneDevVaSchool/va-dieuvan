<?php

namespace App\Jobs;

use App\Models\Attachment;
use App\Services\Ocr\PaperOcrStubService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessAttachmentOcrJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public int $attachmentId) {}

    public function handle(PaperOcrStubService $ocr): void
    {
        $attachment = Attachment::query()->find($this->attachmentId);
        if ($attachment === null || $attachment->kind !== 'paper_scan') {
            return;
        }

        $ocr->process($attachment->fresh());
    }
}
