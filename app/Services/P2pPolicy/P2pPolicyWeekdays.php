<?php

namespace App\Services\P2pPolicy;

use Illuminate\Support\Carbon;

final class P2pPolicyWeekdays
{
    public static function isoWeekdayIncluded(int $mask, int $isoWeekday): bool
    {
        if ($isoWeekday < 1 || $isoWeekday > 7) {
            return false;
        }

        return ($mask & (1 << ($isoWeekday - 1))) !== 0;
    }

    public static function isoWeekdayIncludedForDate(int $mask, Carbon $date): bool
    {
        return self::isoWeekdayIncluded($mask, (int) $date->isoWeekday());
    }
}
