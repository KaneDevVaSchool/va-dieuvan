<?php

namespace App\Services\LegacyImport;

/**
 * Gộp ô merge Excel: mang giá trị từ dòng trên xuống khi ô trống.
 *
 * @param  array<int, mixed>  $values
 * @param  array<int, mixed>  $carry
 * @param  int[]  $indices
 * @return array<int, mixed>
 */
final class LegacyMergeRowFiller
{
    public static function fillValues(array $values, array &$carry, array $indices): array
    {
        foreach ($indices as $idx) {
            $val = $values[$idx] ?? null;
            $empty = $val === null || $val === '';
            if (! $empty) {
                $carry[$idx] = $val;

                continue;
            }
            if (array_key_exists($idx, $carry) && $carry[$idx] !== null && $carry[$idx] !== '') {
                $values[$idx] = $carry[$idx];
            }
        }

        return $values;
    }

    /** @deprecated use fillValues */
    public static function fill(array $cells, array &$carry, array $indices): array
    {
        $values = [];
        foreach ($cells as $i => $c) {
            $values[$i] = $c?->getValue();
        }
        $filled = self::fillValues($values, $carry, $indices);

        return LegacyRowCellAdapter::fromValues($filled);
    }
}
