<?php

namespace App\Services\P2pPolicy;

use App\Models\P2pPolicyTerm;
use App\Models\PolicyGenerationRun;
use App\Models\PolicyRoute;
use App\Models\PolicyStudent;
use App\Support\Messages;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class P2pPolicyReadinessValidator
{
    public function __construct(
        private readonly P2pPolicyRosterResolver $roster,
    ) {}

    /**
     * @return array{ready: bool, issues: list<string>}
     */
    public function assess(P2pPolicyTerm $term): array
    {
        $issues = [];

        if ($term->status !== 'draft') {
            $issues[] = Messages::P2P_POLICY_TERM_NOT_DRAFT;
        }

        if ($term->operating_from === null || $term->operating_to === null) {
            $issues[] = Messages::P2P_POLICY_OPERATING_DATES_REQUIRED;
        } elseif ($term->operating_from->gt($term->operating_to)) {
            $issues[] = Messages::P2P_POLICY_OPERATING_RANGE_INVALID;
        }

        $running = PolicyGenerationRun::query()
            ->where('p2p_policy_term_id', $term->id)
            ->where('status', 'running')
            ->exists();
        if ($running) {
            $issues[] = Messages::P2P_POLICY_GENERATION_IN_PROGRESS;
        }

        /** @var Collection<int, PolicyRoute> $routes */
        $routes = PolicyRoute::query()
            ->where('p2p_policy_term_id', $term->id)
            ->where('is_active', true)
            ->get();

        if ($routes->isEmpty()) {
            $issues[] = Messages::P2P_POLICY_NO_ACTIVE_ROUTES;
        }

        foreach ($routes as $route) {
            if ($route->vehicle_id === null || $route->driver_id === null) {
                $issues[] = sprintf(Messages::P2P_POLICY_ROUTE_MISSING_ASSIGNMENT, $route->name);

                continue;
            }

            $mid = $term->operating_from->copy()->addDays(
                (int) floor($term->operating_from->diffInDays($term->operating_to) / 2),
            );
            if ($this->roster->activeStudentsForRouteOnDate($route, $mid)->isEmpty()) {
                $issues[] = sprintf(Messages::P2P_POLICY_ROUTE_NO_STUDENTS, $route->name);
            }
        }

        return [
            'ready' => $issues === [],
            'issues' => $issues,
        ];
    }

    public function assertReady(P2pPolicyTerm $term): void
    {
        $result = $this->assess($term);
        if (! $result['ready']) {
            throw ValidationException::withMessages([
                'term' => $result['issues'],
            ]);
        }
    }
}
