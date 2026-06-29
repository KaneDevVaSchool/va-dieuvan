<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Giờ lịch trình wizard — naive string = giờ tường VN (khớp JS Date trên trình duyệt VN).
 */
final class TripScheduleInstant
{
    public const DISPLAY_TZ = 'Asia/Ho_Chi_Minh';

    public static function parse(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->copy();
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        try {
            if (self::hasExplicitOffset($raw)) {
                return Carbon::parse($raw);
            }

            $normalized = str_replace(' ', 'T', $raw);
            if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?(\.\d+)?$/', $normalized)) {
                return Carbon::parse($normalized, self::DISPLAY_TZ);
            }

            return Carbon::parse($raw, self::DISPLAY_TZ);
        } catch (\Throwable) {
            return null;
        }
    }

    public static function toIso8601String(mixed $value): ?string
    {
        $parsed = self::parse($value);

        return $parsed?->toIso8601String();
    }

    private static function hasExplicitOffset(string $raw): bool
    {
        if (str_ends_with($raw, 'Z') || str_ends_with($raw, 'z')) {
            return true;
        }

        return (bool) preg_match('/[+-]\d{2}:\d{2}$/', $raw);
    }
}
