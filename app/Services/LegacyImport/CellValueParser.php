<?php

namespace App\Services\LegacyImport;

use Illuminate\Support\Carbon;

trait CellValueParser
{
    /** Returns trimmed string value, or null if blank / not a string type. */
    private function cellStr(array $cells, int $idx): ?string
    {
        $val = ($cells[$idx] ?? null)?->getValue();
        if ($val === null || $val === '') {
            return null;
        }
        if ($val instanceof \DateTimeInterface) {
            return Carbon::instance($val)->format('d/m/Y');
        }
        $s = trim((string) $val);

        return $s === '' ? null : $s;
    }

    /** Returns float value, or null if blank / non-numeric. */
    private function cellFloat(array $cells, int $idx): ?float
    {
        $val = ($cells[$idx] ?? null)?->getValue();
        if ($val === null || $val === '') {
            return null;
        }
        if (is_numeric($val)) {
            return (float) $val;
        }
        // e.g. "1,500,000" → strip commas
        if (is_string($val)) {
            $cleaned = str_replace([',', ' '], '', $val);
            if (is_numeric($cleaned)) {
                return (float) $cleaned;
            }
        }

        return null;
    }

    /** Returns integer value, or null if blank / non-numeric. */
    private function cellInt(array $cells, int $idx): ?int
    {
        $f = $this->cellFloat($cells, $idx);

        return $f !== null ? (int) $f : null;
    }

    /**
     * Returns a Carbon representing only the date portion.
     * Handles both \DateTimeInterface (openspout auto-converted) and
     * raw Excel date serials (float ≥ 40000 & < 100000).
     */
    private function cellDate(array $cells, int $idx): ?Carbon
    {
        $val = ($cells[$idx] ?? null)?->getValue();
        if ($val instanceof \DateTimeInterface) {
            return Carbon::instance($val)->startOfDay();
        }
        if (is_numeric($val) && $val >= 40000 && $val < 100000) {
            // Excel serial: days since 1899-12-30 (epoch offset = 25569 days to 1970-01-01)
            $ts = (int) round(($val - 25569) * 86400);

            return Carbon::createFromTimestampUTC($ts)->startOfDay();
        }

        if (is_string($val)) {
            $parsed = $this->parseDateString(trim($val));
            if ($parsed !== null) {
                return $parsed;
            }
        }

        return null;
    }

    private function parseDateString(string $raw): ?Carbon
    {
        if ($raw === '') {
            return null;
        }

        if (preg_match('/(\d{1,2})[\/\.\-](\d{1,2})[\/\.\-](\d{2,4})/u', $raw, $m)) {
            $d = (int) $m[1];
            $mo = (int) $m[2];
            $y = (int) $m[3];
            if ($y < 100) {
                $y += $y >= 50 ? 1900 : 2000;
            }
            if (checkdate($mo, $d, $y)) {
                return Carbon::createFromDate($y, $mo, $d)->startOfDay();
            }
        }

        foreach (['d/m/Y', 'd-m-Y', 'd.m.Y', 'Y-m-d', 'm/Y', 'm-Y'] as $fmt) {
            $dt = \DateTime::createFromFormat($fmt, $raw);
            if ($dt instanceof \DateTimeInterface) {
                return Carbon::instance($dt)->startOfDay();
            }
        }

        return null;
    }

    /**
     * Returns [hours, minutes] from a time cell.
     * Handles \DateTimeInterface (openspout) and raw time fractions (0.0–1.0).
     *
     * @return array{0:int,1:int}|null
     */
    private function cellTimeParts(array $cells, int $idx): ?array
    {
        $val = ($cells[$idx] ?? null)?->getValue();
        if ($val instanceof \DateTimeInterface) {
            $t = Carbon::instance($val);

            return [$t->hour, $t->minute];
        }
        if (is_float($val) && $val >= 0 && $val < 1) {
            $totalMins = (int) round($val * 24 * 60);

            return [intdiv($totalMins, 60) % 24, $totalMins % 60];
        }

        return null;
    }

    /**
     * Combines a date Carbon with optional time parts into a DB datetime string.
     *
     * @param  array{0:int,1:int}|null  $timeParts
     */
    private function buildDatetime(?Carbon $date, ?array $timeParts): ?string
    {
        if ($date === null) {
            return null;
        }
        $dt = $date->copy();
        if ($timeParts !== null) {
            $dt->setTime($timeParts[0], $timeParts[1], 0);
        }

        return $dt->format('Y-m-d H:i:s');
    }

    /**
     * Returns true when all checked column indices are null/empty —
     * used to detect completely blank rows.
     */
    private function isRowEmpty(array $cells, array $checkIdxs): bool
    {
        foreach ($checkIdxs as $idx) {
            $val = ($cells[$idx] ?? null)?->getValue();
            if ($val !== null && $val !== '') {
                return false;
            }
        }

        return true;
    }

    /** Normalise a vehicle license plate to uppercase with single spaces. */
    private function normalizePlate(string $raw): string
    {
        return mb_strtoupper((string) preg_replace('/\s+/', ' ', trim($raw)));
    }

    /** Canonical key for plate lookup (ignores spaces/dashes). */
    private function normalizePlateKey(string $raw): string
    {
        $u = mb_strtoupper(trim($raw));

        return (string) preg_replace('/[^A-Z0-9.]/u', '', $u);
    }

    /**
     * @param  array<string,int>  $vehicleMap
     */
    private function resolveVehicleId(array $vehicleMap, ?string $rawPlate): ?int
    {
        if ($rawPlate === null || trim($rawPlate) === '') {
            return null;
        }
        $keys = [
            $this->normalizePlateKey($rawPlate),
            $this->normalizePlate($rawPlate),
        ];
        foreach ($keys as $k) {
            if ($k !== '' && isset($vehicleMap[$k])) {
                return (int) $vehicleMap[$k];
            }
        }

        return null;
    }
}
