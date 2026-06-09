<?php

namespace App\Support;

/**
 * Tổng số khách từ wizard_snapshot — khớp `parseGuests` / `isPassengerRowCounted` (JS).
 */
final class DispatchWizardPassengerCount
{
    /**
     * @param  array<string, mixed>  $r
     */
    public static function rowGuestsValue(array $r): int
    {
        $raw = preg_replace('/\s+/', '', trim((string) ($r['guests'] ?? '')));
        if ($raw === '' || ! is_numeric($raw)) {
            return 0;
        }
        $g = (int) $raw;

        return $g >= 1 ? $g : 0;
    }

    /**
     * @param  array<string, mixed>  $snap
     */
    public static function sumFromSnapshot(array $snap, string $tripType = ''): ?int
    {
        $tt = trim($tripType);
        $sum = 0;

        if ($tt === 'cargo') {
            foreach ($snap['cargoRows'] ?? [] as $r) {
                if (! is_array($r) || ! self::cargoSnapshotRowFilled($r)) {
                    continue;
                }
                $q = (int) ($r['qty'] ?? 1);
                $sum += $q >= 1 ? $q : 1;
            }

            return $sum > 0 ? $sum : null;
        }

        if ($tt === 'business') {
            foreach ($snap['businessRows'] ?? [] as $r) {
                if (! is_array($r) || ! self::businessSnapshotRowCounted($r)) {
                    continue;
                }
                $sum += self::rowGuestsValue($r);
            }
        } else {
            foreach ($snap['passengerRows'] ?? [] as $r) {
                if (! is_array($r) || ! self::passengerSnapshotRowCounted($r)) {
                    continue;
                }
                $sum += self::rowGuestsValue($r);
            }
        }

        return $sum > 0 ? $sum : null;
    }

    /**
     * Số khách hiển thị — ưu tiên tổng snapshot (tránh lệch cột passenger_count cũ).
     *
     * @param  array<string, mixed>|null  $wizardSnapshot
     */
    public static function effectiveCount(?array $wizardSnapshot, string $tripType, mixed $storedCount): int
    {
        if ($tripType !== 'cargo' && is_array($wizardSnapshot) && $wizardSnapshot !== []) {
            $fromSnap = self::sumFromSnapshot($wizardSnapshot, $tripType);
            if ($fromSnap !== null && $fromSnap > 0) {
                return $fromSnap;
            }
        }
        $pc = (int) ($storedCount ?? 0);

        return $pc > 0 ? $pc : 0;
    }

    /**
     * Số khách hiển thị từ dispatch request — khớp `dispatchRequestDisplayPassengerCount` (JS).
     *
     * @param  object{student_count_actual?: mixed, passenger_count?: mixed, trip_type?: ?string, wizard_snapshot?: mixed}|null  $dr
     */
    public static function displayFromDispatchRequest(?object $dr): int
    {
        if ($dr === null) {
            return 0;
        }

        $actual = $dr->student_count_actual ?? null;
        if ($actual !== null && $actual !== '') {
            $n = (int) $actual;
            if ($n > 0) {
                return $n;
            }
        }

        $snap = $dr->wizard_snapshot ?? null;
        $tripType = (string) ($dr->trip_type ?? '');
        if (is_array($snap) && $snap !== []) {
            $fromSnap = self::sumFromSnapshot($snap, $tripType);
            if ($fromSnap !== null && $fromSnap > 0) {
                return $fromSnap;
            }
        }

        return self::effectiveCount(
            is_array($snap) ? $snap : null,
            $tripType,
            $dr->passenger_count ?? 0,
        );
    }

    /**
     * @param  array<string, mixed>  $r
     */
    private static function cargoSnapshotRowFilled(array $r): bool
    {
        if (trim((string) ($r['name'] ?? '')) !== '') {
            return true;
        }

        return trim((string) ($r['pickup_place'] ?? '')) !== ''
            || trim((string) ($r['delivery_place'] ?? '')) !== ''
            || trim((string) ($r['pickup_at'] ?? '')) !== ''
            || trim((string) ($r['delivery_at'] ?? '')) !== '';
    }

    /**
     * Dòng có dữ liệu thực — bỏ qua dòng chỉ có giờ auto-sync (tránh +1 khách ảo).
     *
     * @param  array<string, mixed>  $r
     */
    private static function passengerSnapshotRowCounted(array $r): bool
    {
        if (trim((string) ($r['pickup'] ?? '')) !== '' || trim((string) ($r['dropoff'] ?? '')) !== '') {
            return true;
        }
        $pic = trim((string) ($r['person_in_charge'] ?? ''));
        if ($pic !== '' && ! self::isAutoPassengerLabel($pic)) {
            return true;
        }
        if (trim((string) ($r['notes'] ?? '')) !== '') {
            return true;
        }
        if (trim((string) ($r['unit_price'] ?? '')) !== '' || trim((string) ($r['extra_fee'] ?? '')) !== '') {
            return true;
        }
        $g = trim((string) ($r['guests'] ?? ''));

        return $g !== '' && $g !== '1';
    }

    /**
     * @param  array<string, mixed>  $r
     */
    private static function businessSnapshotRowCounted(array $r): bool
    {
        if (trim((string) ($r['pickup'] ?? '')) !== '' || trim((string) ($r['dropoff'] ?? '')) !== '' || trim((string) ($r['waypoint'] ?? '')) !== '') {
            return true;
        }
        if (trim((string) ($r['unit_price'] ?? '')) !== '' || trim((string) ($r['extra_fee'] ?? '')) !== '') {
            return true;
        }
        if (trim((string) ($r['notes'] ?? '')) !== '') {
            return true;
        }
        $g = trim((string) ($r['guests'] ?? ''));

        return $g !== '' && $g !== '1';
    }

    private static function isAutoPassengerLabel(string $name): bool
    {
        $s = trim($name);

        return $s === '' || (bool) preg_match('/^(Khách|Hành khách|Guest|Passengers?|Đoàn công tác|Đoàn|Group)\s*[#№]?\s*\d+$/iu', $s);
    }
}
