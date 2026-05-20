<?php

namespace App\Services\P2pPolicy;

use App\Models\P2pPolicyTerm;
use App\Models\PolicyGenerationRun;
use App\Models\PolicyRoute;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class P2pPolicyReadinessValidator
{
    public function __construct(
        private readonly P2pPolicyRosterResolver $roster,
    ) {}

    /**
     * @return array{ready: bool, issues: list<array{code: string, route?: string}>}
     */
    public function assess(P2pPolicyTerm $term): array
    {
        $issues = [];

        if ($term->status !== 'draft') {
            $issues[] = ['code' => 'term_not_draft'];
        }

        if ($term->operating_from === null || $term->operating_to === null) {
            $issues[] = ['code' => 'operating_dates_required'];
        } elseif ($term->operating_from->gt($term->operating_to)) {
            $issues[] = ['code' => 'operating_range_invalid'];
        }

        $running = PolicyGenerationRun::query()
            ->where('p2p_policy_term_id', $term->id)
            ->where('status', 'running')
            ->exists();
        if ($running) {
            $issues[] = ['code' => 'generation_in_progress'];
        }

        /** @var Collection<int, PolicyRoute> $routes */
        $routes = PolicyRoute::query()
            ->where('p2p_policy_term_id', $term->id)
            ->where('is_active', true)
            ->get();

        if ($routes->isEmpty()) {
            $issues[] = ['code' => 'no_active_routes'];
        }

        foreach ($routes as $route) {
            if ($route->vehicle_id === null || $route->driver_id === null) {
                $issues[] = ['code' => 'route_missing_assignment', 'route' => $route->name];

                continue;
            }

            $mid = $term->operating_from->copy()->addDays(
                (int) floor($term->operating_from->diffInDays($term->operating_to) / 2),
            );
            if ($this->roster->activeStudentsForRouteOnDate($route, $mid)->isEmpty()) {
                $issues[] = ['code' => 'route_no_students', 'route' => $route->name];
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
