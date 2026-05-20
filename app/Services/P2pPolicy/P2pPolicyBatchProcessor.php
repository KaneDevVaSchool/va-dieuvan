<?php

namespace App\Services\P2pPolicy;

use App\Models\P2pPolicyTerm;
use App\Models\PolicyGenerationRun;
use App\Models\PolicyRoute;
use Illuminate\Support\Carbon;

class P2pPolicyBatchProcessor
{
    private const MAX_LOG_LINES = 50;

    public function __construct(
        private readonly P2pPolicyCalendar $calendar,
        private readonly P2pPolicyRosterResolver $roster,
        private readonly P2pPolicyTripMaterializer $materializer,
    ) {}

    /**
     * @return bool true if more batches remain
     */
    public function processNextBatch(PolicyGenerationRun $run): bool
    {
        $run->refresh();
        if ($run->status !== 'running') {
            return false;
        }

        $term = P2pPolicyTerm::query()
            ->with(['holidays', 'skipDates', 'academicTerm'])
            ->findOrFail($run->p2p_policy_term_id);

        $meta = is_array($run->meta) ? $run->meta : [];
        $cursorStr = (string) ($meta['cursor_date'] ?? $term->operating_from->toDateString());
        $activatorUserId = (int) ($meta['activator_user_id'] ?? 0);
        if ($activatorUserId < 1) {
            $activatorUserId = (int) ($term->activated_by ?? 0);
        }

        $batchDays = max(1, (int) config('dispatch.p2p_policy_activation_batch_days', 7));
        $cursor = Carbon::parse($cursorStr)->startOfDay();
        $end = $term->operating_to->copy()->startOfDay();

        if ($cursor->gt($end)) {
            $this->finishRun($run, $term);

            return false;
        }

        $routes = PolicyRoute::query()
            ->where('p2p_policy_term_id', $term->id)
            ->where('is_active', true)
            ->with(['originCampus', 'destCampus'])
            ->get();

        $daysProcessed = 0;
        $created = 0;
        $skipped = 0;
        $log = is_array($meta['log'] ?? null) ? $meta['log'] : [];

        while ($cursor->lte($end) && $daysProcessed < $batchDays) {
            if (! $this->calendar->shouldSkipDate($term, $cursor)) {
                foreach ($routes as $route) {
                    foreach ($this->roster->legsForRouteOnDate($route, $cursor) as $leg) {
                        $result = $this->materializer->materializeSlot(
                            $term,
                            $route,
                            $cursor,
                            $leg,
                            $activatorUserId,
                        );
                        if ($result === 'created') {
                            $created++;
                        } else {
                            $skipped++;
                        }
                    }
                }
            }

            $daysProcessed++;
            $cursor->addDay();
        }

        $meta['cursor_date'] = $cursor->toDateString();
        $log[] = sprintf(
            '%s batch +%d created, +%d skipped (cursor %s)',
            now()->toDateTimeString(),
            $created,
            $skipped,
            $meta['cursor_date'],
        );
        if (count($log) > self::MAX_LOG_LINES) {
            $log = array_slice($log, -self::MAX_LOG_LINES);
        }
        $meta['log'] = $log;

        $run->update([
            'processed_days' => min($run->total_days, $run->processed_days + $daysProcessed),
            'created_slots' => $run->created_slots + $created,
            'skipped_slots' => $run->skipped_slots + $skipped,
            'meta' => $meta,
        ]);

        if ($cursor->gt($end)) {
            $this->finishRun($run, $term);

            return false;
        }

        return true;
    }

    private function finishRun(PolicyGenerationRun $run, P2pPolicyTerm $term): void
    {
        $run->update([
            'status' => 'completed',
            'finished_at' => now(),
            'processed_days' => $run->total_days,
        ]);

        $term->update([
            'status' => 'active',
            'activated_at' => $term->activated_at ?? now(),
        ]);
    }
}
