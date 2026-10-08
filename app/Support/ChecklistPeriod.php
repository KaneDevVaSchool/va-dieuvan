<?php

namespace App\Support;

use App\Models\ChecklistTemplate;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Kỳ checklist theo giờ Việt Nam: daily → "2026-10-05", weekly → "2026-W41" (ISO week).
 */
final class ChecklistPeriod
{
    public static function local(?CarbonInterface $at = null): Carbon
    {
        return ($at ? Carbon::instance($at) : Carbon::now())->copy()->setTimezone(TripScheduleInstant::DISPLAY_TZ);
    }

    public static function key(string $frequency, ?CarbonInterface $at = null): string
    {
        $local = self::local($at);

        return $frequency === ChecklistTemplate::FREQUENCY_WEEKLY
            ? $local->format('o-\WW')
            : $local->toDateString();
    }

    /**
     * Nhãn hiển thị của kỳ, ví dụ "05/10/2026" hoặc "Tuần 41 (05/10 – 11/10/2026)".
     */
    public static function label(string $frequency, string $key): string
    {
        if ($frequency !== ChecklistTemplate::FREQUENCY_WEEKLY) {
            $d = Carbon::createFromFormat('Y-m-d', $key, TripScheduleInstant::DISPLAY_TZ);

            return $d ? $d->format('d/m/Y') : $key;
        }

        if (! preg_match('/^(\d{4})-W(\d{2})$/', $key, $m)) {
            return $key;
        }
        $start = Carbon::now(TripScheduleInstant::DISPLAY_TZ)->setISODate((int) $m[1], (int) $m[2])->startOfWeek(Carbon::MONDAY);
        $end = $start->copy()->addDays(6);

        return sprintf('Tuần %d (%s – %s)', (int) $m[2], $start->format('d/m'), $end->format('d/m/Y'));
    }

    /**
     * Danh sách key các kỳ trong khoảng ngày [from, to] (giờ VN).
     *
     * @return list<string>
     */
    public static function keysBetween(string $frequency, CarbonInterface $from, CarbonInterface $to): array
    {
        $cursor = self::local($from)->startOfDay();
        $end = self::local($to)->startOfDay();
        $keys = [];
        while ($cursor->lte($end)) {
            $keys[] = self::key($frequency, $cursor);
            $cursor->addDay();
        }

        return array_values(array_unique($keys));
    }
}
