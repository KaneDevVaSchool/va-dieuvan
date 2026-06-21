<?php

namespace App\Support;

use App\Models\DispatchRequest;

/**
 * Gợi ý trên danh sách yêu cầu: dòng lịch chờ điều vận cập nhật đơn giá / phụ thu (BM.03).
 */
final class DispatchRequestExtraFeeListHint
{
    /**
     * @return array{
     *     missing_extra_fee: bool,
     *     missing_extra_fee_count: int,
     *     missing_extra_fee_rows: list<array{index: int, label: string}>,
     *     missing_unit_price: bool,
     *     missing_unit_price_count: int,
     *     missing_unit_price_rows: list<array{index: int, label: string}>,
     *     needs_cost_update: bool,
     * }
     */
    public static function forRequest(DispatchRequest $dispatchRequest): array
    {
        $empty = self::emptyHint();

        if ($dispatchRequest->status !== 'pending') {
            return $empty;
        }

        $tripType = (string) ($dispatchRequest->trip_type ?? '');
        if ($tripType === '' || $tripType === 'door_to_door') {
            return $empty;
        }

        $snap = $dispatchRequest->wizard_snapshot;
        if (! is_array($snap) || $snap === []) {
            return $empty;
        }

        if ($tripType === 'cargo') {
            return self::forCargoPending($dispatchRequest, $snap);
        }

        $rowKey = $tripType === 'business' ? 'businessRows' : 'passengerRows';
        $rows = $snap[$rowKey] ?? [];
        if (! is_array($rows)) {
            return $empty;
        }

        $missingExtra = [];
        $missingUnit = [];
        $isBusiness = $tripType === 'business';
        foreach ($rows as $i => $row) {
            if (! is_array($row)) {
                continue;
            }
            $index = (int) $i + 1;
            $label = self::rowRouteLabel($row, $dispatchRequest);
            if (self::rowMissingUnitPrice($row, $isBusiness)) {
                $missingUnit[] = ['index' => $index, 'label' => $label];
            } elseif (self::rowMissingExtraFee($row, $isBusiness)) {
                $missingExtra[] = ['index' => $index, 'label' => $label];
            }
        }

        return self::buildHint($missingUnit, $missingExtra);
    }

    /**
     * @param  array<string, mixed>  $snap
     * @return array{
     *     missing_extra_fee: bool,
     *     missing_extra_fee_count: int,
     *     missing_extra_fee_rows: list<array{index: int, label: string}>,
     *     missing_unit_price: bool,
     *     missing_unit_price_count: int,
     *     missing_unit_price_rows: list<array{index: int, label: string}>,
     *     needs_cost_update: bool,
     * }
     */
    private static function forCargoPending(DispatchRequest $dispatchRequest, array $snap): array
    {
        $rows = $snap['cargoRows'] ?? [];
        if (! is_array($rows)) {
            return self::emptyHint();
        }

        $missingUnit = [];
        foreach ($rows as $i => $row) {
            if (! is_array($row)) {
                continue;
            }
            if (! self::cargoRowMissingCost($row)) {
                continue;
            }
            $missingUnit[] = [
                'index' => (int) $i + 1,
                'label' => self::cargoRowLabel($row, $dispatchRequest),
            ];
        }

        return self::buildHint($missingUnit, []);
    }

    /**
     * @param  list<array{index: int, label: string}>  $missingUnit
     * @param  list<array{index: int, label: string}>  $missingExtra
     * @return array{
     *     missing_extra_fee: bool,
     *     missing_extra_fee_count: int,
     *     missing_extra_fee_rows: list<array{index: int, label: string}>,
     *     missing_unit_price: bool,
     *     missing_unit_price_count: int,
     *     missing_unit_price_rows: list<array{index: int, label: string}>,
     *     needs_cost_update: bool,
     * }
     */
    private static function buildHint(array $missingUnit, array $missingExtra): array
    {
        $unitCount = count($missingUnit);
        $extraCount = count($missingExtra);

        return [
            'missing_extra_fee' => $extraCount > 0,
            'missing_extra_fee_count' => $extraCount,
            'missing_extra_fee_rows' => $missingExtra,
            'missing_unit_price' => $unitCount > 0,
            'missing_unit_price_count' => $unitCount,
            'missing_unit_price_rows' => $missingUnit,
            'needs_cost_update' => $unitCount > 0 || $extraCount > 0,
        ];
    }

    /**
     * @return array{
     *     missing_extra_fee: bool,
     *     missing_extra_fee_count: int,
     *     missing_extra_fee_rows: list<array{index: int, label: string}>,
     *     missing_unit_price: bool,
     *     missing_unit_price_count: int,
     *     missing_unit_price_rows: list<array{index: int, label: string}>,
     *     needs_cost_update: bool,
     * }
     */
    private static function emptyHint(): array
    {
        return self::buildHint([], []);
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
    private static function rowMissingUnitPrice(array $r, bool $isBusiness): bool
    {
        if (trim((string) ($r['unit_price'] ?? '')) !== '') {
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
    private static function cargoRowMissingCost(array $r): bool
    {
        if (trim((string) ($r['cost'] ?? '')) !== '') {
            return false;
        }

        return self::cargoSnapshotRowCounted($r);
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
    private static function cargoRowLabel(array $r, DispatchRequest $dispatchRequest): string
    {
        $name = trim((string) ($r['name'] ?? ''));
        if ($name !== '') {
            return $name;
        }

        return self::rowRouteLabel($r, $dispatchRequest);
    }

    /**
     * @param  array<string, mixed>  $r
     */
    private static function cargoSnapshotRowCounted(array $r): bool
    {
        return trim((string) ($r['name'] ?? '')) !== '';
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
