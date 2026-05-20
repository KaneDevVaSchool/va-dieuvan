<?php

namespace App\Services\P2pPolicy;

use App\Models\P2pPolicyTerm;
use App\Models\PolicyGenerationRun;
use App\Models\PolicyRoute;
use App\Jobs\PolicyGenerateTripsBatchJob;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class P2pPolicyActivationService
{
    public function __construct(
        private readonly P2pPolicyReadinessValidator $readiness,
        private readonly P2pPolicyGenerationPlanner $planner,
    ) {}

    public function activate(P2pPolicyTerm $term, int $userId): PolicyGenerationRun
    {
        $this->readiness->assertReady($term);

        $term->load(['holidays', 'skipDates']);

        /** @var PolicyRoute[] $routes */
        $routes = PolicyRoute::query()
            ->where('p2p_policy_term_id', $term->id)
            ->where('is_active', true)
            ->get()
            ->all();

        $estimate = $this->planner->estimate($term, $routes);

        return DB::transaction(function () use ($term, $userId, $estimate) {
            $run = PolicyGenerationRun::create([
                'p2p_policy_term_id' => $term->id,
                'status' => 'running',
                'total_days' => $estimate['total_days'],
                'processed_days' => 0,
                'total_slots' => $estimate['total_slots'],
                'created_slots' => 0,
                'skipped_slots' => 0,
                'started_at' => now(),
                'meta' => [
                    'cursor_date' => $term->operating_from->toDateString(),
                    'activator_user_id' => $userId,
                    'log' => [],
                ],
            ]);

            $term->update([
                'status' => 'generating',
                'generation_run_id' => $run->id,
                'activated_at' => now(),
                'activated_by' => $userId,
            ]);

            PolicyGenerateTripsBatchJob::dispatch($run->id);

            return $run;
        });
    }

    /**
     * Materialize trips for active terms within horizon (scheduled command).
     */
    public function materializeHorizonForTerm(P2pPolicyTerm $term, int $horizonDays, int $systemUserId): int
    {
        if ($term->status !== 'active') {
            return 0;
        }

        $term->load(['holidays', 'skipDates']);
        $today = Carbon::today();
        $until = $today->copy()->addDays($horizonDays);

        $routes = PolicyRoute::query()
            ->where('p2p_policy_term_id', $term->id)
            ->where('is_active', true)
            ->get();

        $materializer = app(P2pPolicyTripMaterializer::class);
        $calendar = app(P2pPolicyCalendar::class);
        $roster = app(P2pPolicyRosterResolver::class);

        $created = 0;
        $dates = $calendar->operatingDatesInRange($term, $today, $until);
        foreach ($dates as $date) {
            foreach ($routes as $route) {
                foreach ($roster->legsForRouteOnDate($route, $date) as $leg) {
                    if ($materializer->materializeSlot($term, $route, $date, $leg, $systemUserId) === 'created') {
                        $created++;
                    }
                }
            }
        }

        return $created;
    }
}
