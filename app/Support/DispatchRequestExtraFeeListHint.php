<?php

namespace App\Support;

use App\Models\DispatchRequest;

/**
 * Gợi ý trên danh sách yêu cầu: dòng lịch đã có đơn giá nhưng chưa nhập cột phụ thu (BM.03).
 */
final class DispatchRequestExtraFeeListHint
{
    /**
     * @return array{
     *     missing_extra_fee: bool,
     *     missing_extra_fee_count: int,
     *     missing_extra_fee_rows: list<array{index: int, label: string}>
     * }
     */
    public static function forRequest(DispatchRequest $dispatchRequest): array
    {
        $empty = [
            'missing_extra_fee' => false,
            'missing_extra_fee_count' => 0,
            'missing_extra_fee_rows' => [],
        ];

        if ($dispatchRequest->status !== 'pending') {
            return $empty;
        }

        $tripType = (string) ($dispatchRequest->trip_type ?? '');
        if ($tripType === '' || $tripType === 'cargo' || $tripType === 'door_to_door') {
            return $empty;
        }

        $snap = $dispatchRequest->wizard_snapshot;
        if (! is_array($snap) || $snap === []) {
            return $empty;
        }

        $rowKey = $tripType === 'business' ? 'businessRows' : 'passengerRows';
        $rows = $snap[$rowKey] ?? [];
        if (! is_array($rows)) {
            return $empty;
        }

        $missing = [];
        $isBusiness = $tripType === 'business';
        foreach ($rows as $i => $row) {
            if (! is_array($row)) {
                continue;
            }
            if (! self::rowMissingExtraFee($row, $isBusiness)) {
                continue;
            }
            $missing[] = [
                'index' => (int) $i + 1,
                'label' => self::rowRouteLabel($row, $dispatchRequest),
            ];
        }

        $count = count($missing);

        return [
            'missing_extra_fee' => $count > 0,
            'missing_extra_fee_count' => $count,
            'missing_extra_fee_rows' => $missing,
        ];
    }

    /**
     * @param  array<string, mixed>  $r
     */
    private static function rowMissingExtraFee(array $r, bool $isBusiness): bool
    {
        if (trim((string) ($r['extra_fee'] ?? '')) !== '') {
            return false;
        }
        if (trim((string) ($r['unit_price'] ?? '')) === '') {
            return false;
        }
        if ($isBusiness) {
            return self::businessSnapshotRowCounted($r);
        }

        return self::passengerSnapshotRowCounted($r);
    }

    /**
     * @param  array<string, mixed>  $r
     */
    private static function rowRouteLabel(array $r, DispatchRequest $dispatchRequest): string
    {
        $pickup = trim((string) ($r['pickup'] ?? ''));
        $dropoff = trim((string) ($r['dropoff'] ?? ''));
        if ($pickup !== '' || $dropoff !== '') {
            return trim("{$pickup} → {$dropoff}", ' →');
        }

        $origin = trim((string) ($dispatchRequest->origin ?? ''));
        $destination = trim((string) ($dispatchRequest->destination ?? ''));
        if ($origin !== '' || $destination !== '') {
            return trim("{$origin} → {$destination}", ' →');
        }

        return '';
    }

    /**
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
