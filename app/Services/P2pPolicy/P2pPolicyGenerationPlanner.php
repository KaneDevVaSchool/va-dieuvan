<?php

namespace App\Services\P2pPolicy;

use App\Models\P2pPolicyTerm;
use App\Models\PolicyRoute;
use App\Support\P2pPolicy;
use Illuminate\Support\Carbon;

class P2pPolicyGenerationPlanner
{
    public function __construct(
        private readonly P2pPolicyCalendar $calendar,
        private readonly P2pPolicyRosterResolver $roster,
    ) {}

    /**
     * @return array{total_days: int, total_slots: int}
     */
    public function estimate(P2pPolicyTerm $term, iterable $routes): array
    {
        $dates = $this->calendar->operatingDatesInRange(
            $term,
            $term->operating_from->copy(),
            $term->operating_to->copy(),
        );

        $slots = 0;
        foreach ($dates as $date) {
            foreach ($routes as $route) {
                if (! $route instanceof PolicyRoute || ! $route->is_active) {
                    continue;
                }
                $slots += count($this->roster->legsForRouteOnDate($route, $date));
            }
        }

        return [
            'total_days' => count($dates),
            'total_slots' => $slots,
        ];
    }

    /**
     * @return array{start: Carbon, end: Carbon}|null
     */
    public function legWindow(PolicyRoute $route, P2pPolicyTerm $term, string $leg, Carbon $runDate): ?array
    {
        if ($leg === P2pPolicy::LEG_MORNING) {
            $start = $route->morning_start ?? $term->default_morning_start;
            $end = $route->morning_end ?? $term->default_morning_end;
        } elseif ($leg === P2pPolicy::LEG_AFTERNOON) {
            $start = $route->afternoon_start ?? $term->default_afternoon_start;
            $end = $route->afternoon_end ?? $term->default_afternoon_end;
        } else {
            return null;
        }

        $dateStr = $runDate->toDateString();

        return [
            'start' => Carbon::parse("{$dateStr} {$this->normalizeTime($start)}"),
            'end' => Carbon::parse("{$dateStr} {$this->normalizeTime($end)}"),
        ];
    }

    private function normalizeTime(mixed $time): string
    {
        if ($time instanceof \DateTimeInterface) {
            return $time->format('H:i:s');
        }
        $s = trim((string) $time);
        if (preg_match('/^\d{2}:\d{2}$/', $s)) {
            return "{$s}:00";
        }

        return $s !== '' ? $s : '00:00:00';
    }
}
