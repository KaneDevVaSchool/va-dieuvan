<?php

namespace App\Services\P2pPolicy;

use App\Models\P2pPolicyTerm;
use Illuminate\Support\Carbon;

class P2pPolicyCalendar
{
    public function __construct(
        private readonly P2pPolicyRosterResolver $roster,
        private readonly P2pPolicyFixedHolidays $fixedHolidays,
    ) {}

    public function shouldSkipDate(P2pPolicyTerm $term, Carbon $date): bool
    {
        $mask = (int) ($term->weekdays_mask ?? 31);
        if (! P2pPolicyWeekdays::isoWeekdayIncludedForDate($mask, $date)) {
            return true;
        }

        $dateStr = $date->toDateString();

        if ($term->relationLoaded('holidays')) {
            foreach ($term->holidays as $h) {
                if ($h->holiday_date->toDateString() === $dateStr) {
                    return true;
                }
            }
        } else {
            if ($term->holidays()->whereDate('holiday_date', $dateStr)->exists()) {
                return true;
            }
        }

        if ($term->relationLoaded('skipDates')) {
            foreach ($term->skipDates as $s) {
                if ($s->skip_date->toDateString() === $dateStr) {
                    return true;
                }
            }
        } else {
            if ($term->skipDates()->whereDate('skip_date', $dateStr)->exists()) {
                return true;
            }
        }

        if ($term->exclude_fixed_holidays && $this->fixedHolidays->isFixedHoliday($date)) {
            return true;
        }

        return false;
    }

    /**
     * @return list<Carbon>
     */
    public function operatingDatesInRange(P2pPolicyTerm $term, Carbon $from, Carbon $to): array
    {
        $dates = [];
        $cursor = $from->copy()->startOfDay();
        $end = $to->copy()->startOfDay();

        while ($cursor->lte($end)) {
            if ($cursor->gte($term->operating_from->startOfDay())
                && $cursor->lte($term->operating_to->startOfDay())
                && ! $this->shouldSkipDate($term, $cursor)
            ) {
                $dates[] = $cursor->copy();
            }
            $cursor->addDay();
        }

        return $dates;
    }
}
