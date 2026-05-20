<?php

namespace App\Services\P2pPolicy;

use Illuminate\Support\Carbon;

class P2pPolicyFixedHolidays
{
    /**
     * @return list<array{holiday_date: string, label: string}>
     */
    public function datesInRange(Carbon $from, Carbon $to): array
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->startOfDay();
        if ($from->gt($to)) {
            return [];
        }

        $map = [];

        $startYear = (int) $from->year;
        $endYear = (int) $to->year;
        $recurring = config('p2p_policy_fixed_holidays.recurring', []);

        for ($year = $startYear; $year <= $endYear; $year++) {
            foreach ($recurring as $md => $label) {
                $dateStr = sprintf('%04d-%s', $year, $md);
                try {
                    $d = Carbon::createFromFormat('Y-m-d', $dateStr)->startOfDay();
                } catch (\Throwable) {
                    continue;
                }
                if ($d->gte($from) && $d->lte($to)) {
                    $map[$dateStr] = $label;
                }
            }
        }

        foreach (config('p2p_policy_fixed_holidays.by_date', []) as $dateStr => $label) {
            try {
                $d = Carbon::parse($dateStr)->startOfDay();
            } catch (\Throwable) {
                continue;
            }
            if ($d->gte($from) && $d->lte($to)) {
                $map[$dateStr] = $label;
            }
        }

        ksort($map);

        $items = [];
        foreach ($map as $dateStr => $label) {
            $items[] = ['holiday_date' => $dateStr, 'label' => $label];
        }

        return $items;
    }

    public function isFixedHoliday(Carbon $date): bool
    {
        $date = $date->copy()->startOfDay();
        $md = $date->format('m-d');
        if (isset(config('p2p_policy_fixed_holidays.recurring', [])[$md])) {
            return true;
        }

        return isset(config('p2p_policy_fixed_holidays.by_date', [])[$date->toDateString()]);
    }
}
