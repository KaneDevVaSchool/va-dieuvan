<?php

namespace App\Jobs;

use App\Services\SignedDocuments\SignedDocumentPipelineService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessSignedDocumentPipelineJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(public int $signedDocumentVersionId) {}

    public function uniqueId(): string
    {
        return 'signed-document-pipeline:'.$this->signedDocumentVersionId;
    }

    public function handle(SignedDocumentPipelineService $pipeline): void
    {
        $pipeline->run($this->signedDocumentVersionId);
    }
}
