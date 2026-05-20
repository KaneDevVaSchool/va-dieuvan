<?php

namespace App\Jobs;

use App\Models\PolicyGenerationRun;
use App\Services\P2pPolicy\P2pPolicyBatchProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class PolicyGenerateTripsBatchJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $generationRunId,
    ) {}

    public function handle(P2pPolicyBatchProcessor $processor): void
    {
        $run = PolicyGenerationRun::query()->find($this->generationRunId);
        if ($run === null || $run->status !== 'running') {
            return;
        }

        try {
            $hasMore = $processor->processNextBatch($run);
            if ($hasMore) {
                self::dispatch($this->generationRunId);
            }
        } catch (Throwable $e) {
            $run->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'finished_at' => now(),
            ]);

            $run->p2pPolicyTerm?->update(['status' => 'draft']);

            throw $e;
        }
    }
}
