<?php

namespace App\Jobs;

use App\Services\Auditing\AuditLogger;
use App\Services\P2pPolicy\PolicyStudentImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class PolicyStudentImportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $importJobId,
        public string $storagePath,
        public int $p2pPolicyTermId,
        public int $userId,
        public string $originalFilename,
    ) {}

    public function handle(PolicyStudentImportService $importer): void
    {
        Cache::put("p2p_policy_import:{$this->importJobId}", ['status' => 'running'], 3600);

        $result = $importer->importFromStoragePath(
            $this->storagePath,
            $this->p2pPolicyTermId,
            $this->originalFilename,
        );

        Cache::put("p2p_policy_import:{$this->importJobId}", [
            'status' => 'completed',
            'imported' => $result['imported'],
            'errors' => $result['errors'],
        ], 86400);

        Storage::delete($this->storagePath);

        app(AuditLogger::class)->log(
            actorId: $this->userId,
            event: 'p2p_policy.students.import',
            auditable: null,
            before: null,
            after: $result,
            metadata: ['import_job_id' => $this->importJobId],
        );
    }
}
