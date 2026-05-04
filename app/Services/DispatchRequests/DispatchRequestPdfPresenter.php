<?php

namespace App\Services\DispatchRequests;

use App\Models\DispatchRequest;
use App\Support\DispatchBm03TargetOptions;
use Illuminate\Support\Carbon;

final class DispatchRequestPdfPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function forModel(DispatchRequest $dispatchRequest): array
    {
        $dispatchRequest->loadMissing('requester:id,name,email,phone');

        $snapshot = is_array($dispatchRequest->wizard_snapshot) ? $dispatchRequest->wizard_snapshot : [];
        $form = isset($snapshot['form']) && is_array($snapshot['form']) ? $snapshot['form'] : [];

        $cargoRows = self::normalizeList($snapshot['cargoRows'] ?? []);
        $passengerRows = self::normalizeList($snapshot['passengerRows'] ?? []);
        $businessRows = self::normalizeList($snapshot['businessRows'] ?? []);

        $user = $dispatchRequest->requester;

        $aName = self::nzString($form['requester_name'] ?? null) ?: self::nzString($user?->name);
        $aEmail = self::nzString($form['requester_email'] ?? null) ?: self::nzString($user?->email);
        $aPhone = self::nzString($form['requester_phone'] ?? null) ?: self::nzString($user?->phone);
        $aUnit = self::nzString($form['requester_unit'] ?? null);

        $selectedTargets = [];
        if (isset($form['targets']) && is_array($form['targets'])) {
            foreach ($form['targets'] as $t) {
                $s = self::nzString($t);
                if ($s !== '') {
                    $selectedTargets[$s] = true;
                }
            }
        }

        $targetGrid = [];
        foreach (DispatchBm03TargetOptions::OPTIONS as $label) {
            $targetGrid[] = [
                'label' => $label,
                'checked' => isset($selectedTargets[$label]),
            ];
        }

        $sectionERows = self::buildSectionERows($form, $cargoRows, $passengerRows, $businessRows);
        $lineSum = 0;
        foreach ($sectionERows as $row) {
            if (self::nzString($row['name'] ?? null) !== '') {
                $lineSum += self::parseMoney($row['cost'] ?? null);
            }
        }
        $extras = 0;
        if (! empty($form['need_porters'])) {
            $extras += self::parseMoney($form['porter_cost'] ?? null);
        }
        if (! empty($form['interprovincial'])) {
            $extras += self::parseMoney($form['interprovincial_cost'] ?? null);
        }
        $grandTotal = $lineSum + $extras;

        $basisFileName = self::nzString($form['basisFileName'] ?? null);
        $basisLine = $basisFileName !== ''
            ? 'Đính kèm tệp «'.$basisFileName.'»'
            : '';

        $poCode = self::nzString($dispatchRequest->paper_reference);
        $g2Date = '';
        if ($dispatchRequest->paper_received_at) {
            $g2Date = Carbon::parse($dispatchRequest->paper_received_at)->format('d/m/Y');
        } elseif ($dispatchRequest->status === 'approved' && $dispatchRequest->updated_at) {
            $g2Date = $dispatchRequest->updated_at->format('d/m/Y');
        }

        $logoPath = public_path('images/logo/vas-logo.png');
        $logoDataUri = '';
        if (is_readable($logoPath)) {
            $raw = @file_get_contents($logoPath);
            if ($raw !== false) {
                $logoDataUri = 'data:image/png;base64,'.base64_encode($raw);
            }
        }

        return [
            'dispatchRequest' => $dispatchRequest,
            'logoDataUri' => $logoDataUri,
            'aName' => $aName,
            'aEmail' => $aEmail,
            'aPhone' => $aPhone,
            'aUnit' => $aUnit,
            'purpose' => self::nzString($form['purpose'] ?? null),
            'basisLine' => $basisLine,
            'proposedDate' => self::fmtDateStr($form['proposed_date'] ?? null),
            'dateNeeded' => self::fmtDateStr($form['date_needed'] ?? null),
            'isUrgent' => ! empty($form['is_urgent']),
            'urgentReason' => self::nzString($form['urgent_reason'] ?? null),
            'targetGrid' => $targetGrid,
            'coordName' => self::nzString($form['coordinator_name'] ?? null),
            'coordEmail' => self::nzString($form['coordinator_email'] ?? null),
            'coordPhone' => self::nzString($form['coordinator_phone'] ?? null),
            'sectionERows' => $sectionERows,
            'grandTotal' => $grandTotal,
            'grandTotalFmt' => self::formatVnd($grandTotal),
            'cargoExtraNotes' => self::nzString($form['cargo_extra_notes'] ?? null),
            'needPorters' => ! empty($form['need_porters']),
            'porterQty' => self::nzString($form['porter_qty'] ?? null),
            'porterCost' => self::nzString($form['porter_cost'] ?? null),
            'interprovincial' => ! empty($form['interprovincial']),
            'interprovincialCost' => self::nzString($form['interprovincial_cost'] ?? null),
            'poCode' => $poCode,
            'g2Date' => $g2Date,
            'tripType' => self::nzString($form['trip_type'] ?? $dispatchRequest->trip_type),
        ];
    }

    /**
     * @param  mixed  $v
     */
    private static function normalizeList($v): array
    {
        return is_array($v) ? $v : [];
    }

    private static function nzString(?string $s): string
    {
        if ($s === null) {
            return '';
        }

        return trim($s);
    }

    private static function fmtDateStr(?string $d): string
    {
        $s = self::nzString($d);
        if ($s === '') {
            return '';
        }
        try {
            return Carbon::parse($s)->format('d/m/Y');
        } catch (\Throwable) {
            return $s;
        }
    }

    public static function parseMoney(mixed $v): int
    {
        if ($v === null || $v === '') {
            return 0;
        }
        $s = str_replace(['.', ' ', ','], '', (string) $v);
        if ($s === '' || ! is_numeric($s)) {
            return 0;
        }

        return (int) round((float) $s);
    }

    public static function formatVnd(int $n): string
    {
        return number_format($n, 0, ',', '.').' đ';
    }

    public static function formatCostCell(string $name, string $cost): string
    {
        if ($name === '') {
            return '';
        }

        return self::formatVnd(self::parseMoney($cost));
    }

    /**
     * @param  array<string, mixed>  $form
     * @param  array<int, array<string, mixed>>  $cargoRows
     * @param  array<int, array<string, mixed>>  $passengerRows
     * @param  array<int, array<string, mixed>>  $businessRows
     * @return array<int, array<string, string>>
     */
    private static function buildSectionERows(
        array $form,
        array $cargoRows,
        array $passengerRows,
        array $businessRows,
    ): array {
        $trip = $form['trip_type'] ?? '';
        $built = [];
        if ($trip === 'cargo') {
            foreach ($cargoRows as $r) {
                if (! self::nzString($r['name'] ?? null)) {
                    continue;
                }
                $built[] = [
                    'name' => self::nzString($r['name'] ?? null),
                    'qty' => self::nzString($r['qty'] ?? null) ?: '—',
                    'dim' => self::nzString($r['dimensions'] ?? null) ?: '—',
                    'weight' => self::nzString($r['weight'] ?? null) ?: '—',
                    'inotes' => self::nzString($r['item_notes'] ?? null),
                    'puTime' => self::nzString($r['pickup_at'] ?? null) ?: '—',
                    'puPlace' => self::nzString($r['pickup_place'] ?? null) ?: '—',
                    'puContact' => self::nzString($r['pickup_contact'] ?? null) ?: '—',
                    'delTime' => self::nzString($r['delivery_at'] ?? null) ?: '—',
                    'delPlace' => self::nzString($r['delivery_place'] ?? null) ?: '—',
                    'delContact' => self::nzString($r['delivery_contact'] ?? null) ?: '—',
                    'transport' => self::nzString($r['transport_note'] ?? null) ?: '—',
                    'cost' => self::nzString($r['cost'] ?? null) !== ''
                        ? self::nzString($r['cost'] ?? null)
                        : '0',
                ];
            }
        } else {
            foreach ($passengerRows as $r) {
                if (! self::isPassengerRowFilled($r)) {
                    continue;
                }
                $built[] = self::passengerRowToE($r);
            }
            foreach ($businessRows as $r) {
                if (! self::isBusinessRowFilled($r)) {
                    continue;
                }
                $built[] = self::businessRowToE($r);
            }
        }

        $empty = [
            'name' => '', 'qty' => '', 'dim' => '', 'weight' => '', 'inotes' => '',
            'puTime' => '', 'puPlace' => '', 'puContact' => '',
            'delTime' => '', 'delPlace' => '', 'delContact' => '',
            'transport' => '', 'cost' => '',
        ];
        while (count($built) < 10) {
            $built[] = $empty;
        }

        return array_slice($built, 0, 10);
    }

    /**
     * @param  array<string, mixed>  $r
     */
    private static function isPassengerRowFilled(array $r): bool
    {
        if (self::nzString($r['pickup'] ?? null) || self::nzString($r['dropoff'] ?? null)) {
            return true;
        }
        if (self::nzString($r['depart_at'] ?? null) || self::nzString($r['return_at'] ?? null)) {
            return true;
        }
        if (self::nzString($r['person_in_charge'] ?? null) || self::nzString($r['notes'] ?? null)) {
            return true;
        }
        if (self::parseMoney($r['unit_price'] ?? null) || self::parseMoney($r['extra_fee'] ?? null)) {
            return true;
        }
        $g = self::nzString($r['guests'] ?? null);

        return $g !== '' && $g !== '1';
    }

    /**
     * @param  array<string, mixed>  $r
     */
    private static function isBusinessRowFilled(array $r): bool
    {
        if (self::nzString($r['pickup'] ?? null) || self::nzString($r['dropoff'] ?? null) || self::nzString($r['waypoint'] ?? null)) {
            return true;
        }
        if (self::nzString($r['depart_at'] ?? null) || self::nzString($r['return_at'] ?? null)) {
            return true;
        }
        if (self::parseMoney($r['unit_price'] ?? null) || self::parseMoney($r['extra_fee'] ?? null)) {
            return true;
        }
        if (self::nzString($r['notes'] ?? null)) {
            return true;
        }
        $g = self::nzString($r['guests'] ?? null);

        return $g !== '' && $g !== '1';
    }

    /**
     * @param  array<string, mixed>  $r
     * @return array<string, string>
     */
    private static function passengerRowToE(array $r): array
    {
        $line = 'Hành khách / chương trình';
        $total = self::parseMoney($r['unit_price'] ?? null) + self::parseMoney($r['extra_fee'] ?? null);

        return [
            'name' => $line,
            'qty' => self::nzString($r['guests'] ?? null) ?: '—',
            'dim' => '—',
            'weight' => '—',
            'inotes' => self::nzString($r['notes'] ?? null),
            'puTime' => self::nzString($r['depart_at'] ?? null) ?: '—',
            'puPlace' => self::nzString($r['pickup'] ?? null) ?: '—',
            'puContact' => self::nzString($r['person_in_charge'] ?? null) ?: '—',
            'delTime' => self::nzString($r['return_at'] ?? null) ?: '—',
            'delPlace' => self::nzString($r['dropoff'] ?? null) ?: '—',
            'delContact' => '—',
            'transport' => 'Điểm — Điểm',
            'cost' => $total > 0 ? (string) $total : '0',
        ];
    }

    /**
     * @param  array<string, mixed>  $r
     * @return array<string, string>
     */
    private static function businessRowToE(array $r): array
    {
        $total = self::parseMoney($r['unit_price'] ?? null) + self::parseMoney($r['extra_fee'] ?? null);

        return [
            'name' => 'Công tác',
            'qty' => self::nzString($r['guests'] ?? null) ?: '—',
            'dim' => self::nzString($r['waypoint'] ?? null) ?: '—',
            'weight' => '—',
            'inotes' => self::nzString($r['notes'] ?? null),
            'puTime' => self::nzString($r['depart_at'] ?? null) ?: '—',
            'puPlace' => self::nzString($r['pickup'] ?? null) ?: '—',
            'puContact' => '—',
            'delTime' => self::nzString($r['return_at'] ?? null) ?: '—',
            'delPlace' => self::nzString($r['dropoff'] ?? null) ?: '—',
            'delContact' => '—',
            'transport' => 'Công tác',
            'cost' => $total > 0 ? (string) $total : '0',
        ];
    }
}
