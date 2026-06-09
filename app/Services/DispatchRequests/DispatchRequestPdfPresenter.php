<?php

namespace App\Services\DispatchRequests;

use App\Models\DispatchRequest;
use App\Support\DispatchBm03TargetOptions;
use Illuminate\Support\Carbon;

final class DispatchRequestPdfPresenter
{
    private const PDF_TZ = 'Asia/Ho_Chi_Minh';

    public static function formatCostCell(string $name, string $cost): string
    {
        $amount = self::parseMoney($cost);
        if ($name === '' || $amount === 0) {
            return '';
        }

        return number_format($amount, 0, ',', '.').' đ';
    }

    /**
     * @return array<string, mixed>
     */
    public static function buildPdfData(DispatchRequest $dr): array
    {
        $dr->loadMissing(['requester:id,name,email,phone']);

        $snapshot = is_array($dr->wizard_snapshot) ? $dr->wizard_snapshot : [];
        $form = isset($snapshot['form']) && is_array($snapshot['form']) ? $snapshot['form'] : [];

        $tripTypeRaw = (string) $dr->trip_type;
        $isCargo = $dr->trip_type === 'cargo';
        $isP2P = $dr->trip_type === 'point_to_point';
        $isBusiness = $dr->trip_type === 'business';
        $isDoor = $dr->trip_type === 'door_to_door';

        $cargoRows = self::normalizeList($snapshot['cargoRows'] ?? []);
        $passengerRows = self::normalizeList($snapshot['passengerRows'] ?? []);
        $businessRows = self::normalizeList($snapshot['businessRows'] ?? []);

        $user = $dr->requester;

        $aName = self::nzString($form['requester_name'] ?? null) ?: self::nzString($user?->name);
        $aEmail = self::nzString($form['requester_email'] ?? null) ?: self::nzString($user?->email);
        $aPhone = self::nzString($form['requester_phone'] ?? null) ?: self::nzString($user?->phone);
        $aUnit = self::nzString($form['requester_unit'] ?? null);

        $selectedTargets = [];
        if (isset($form['targets']) && is_array($form['targets'])) {
            foreach ($form['targets'] as $t) {
                $s = self::nzString(is_scalar($t) ? (string) $t : null);
                if ($s !== '') {
                    $selectedTargets[] = $s;
                }
            }
        }

        $targetOptions = config('dispatch.target_options');
        if (! is_array($targetOptions) || $targetOptions === []) {
            $targetOptions = collect(DispatchBm03TargetOptions::OPTIONS)
                ->mapWithKeys(fn (string $l) => [$l => $l])
                ->all();
        }
        $targetGrid = collect($targetOptions)
            ->map(fn (string $label, string $key) => [
                'label' => $label,
                'checked' => in_array($key, $selectedTargets, true),
            ])
            ->values()
            ->all();

        $showPassenger = ! $isCargo && ! $isBusiness;

        $cargoRaw = $isCargo ? self::collectCargoModels($cargoRows) : collect();
        $passRaw = $showPassenger ? self::collectPassengerModels($passengerRows) : collect();
        $bizRaw = $isBusiness ? self::collectBusinessModels($businessRows) : collect();

        $cargoSectionRows = $isCargo
            ? $cargoRaw->map(fn (array $r) => self::cargoRowToPdf($r))->all()
            : [];

        $passengerSectionRows = $showPassenger
            ? $passRaw->map(fn (array $r) => self::passengerRowToPdf($r))->all()
            : [];

        $businessSectionRows = $isBusiness
            ? $bizRaw->map(fn (array $r) => self::businessRowToPdf($r))->all()
            : [];

        $grandTotal = match (true) {
            $isCargo => $cargoRaw->sum(fn (array $r) => self::parseMoney($r['cost'] ?? null)),
            $isBusiness => $bizRaw->sum(fn (array $r) => self::parseMoney($r['unit_price'] ?? null) + self::parseMoney($r['extra_fee'] ?? null)),
            default => $passRaw->sum(fn (array $r) => self::parseMoney($r['unit_price'] ?? null) + self::parseMoney($r['extra_fee'] ?? null)),
        };
        $grandTotalFmt = $grandTotal > 0
            ? number_format($grandTotal, 0, ',', '.').' đ'
            : '0 đ';

        $tripTypeLabels = [
            'door_to_door' => 'Đưa đón tận nơi',
            'point_to_point' => 'Điểm — Điểm',
            'business' => 'Công tác',
            'cargo' => 'Điều chuyển hàng hóa',
        ];

        $basisLine = self::basisLineFromForm($form);
        $g2Date = self::resolveG2Date($dr);

        return [
            'dispatchRequest' => $dr,
            'isCargo' => $isCargo,
            'isP2P' => $isP2P,
            'isBusiness' => $isBusiness,
            'isDoor' => $isDoor,
            'logoDataUri' => self::resolveLogoDataUri(),
            'targetGrid' => $targetGrid,
            'cargoSectionRows' => $cargoSectionRows,
            'passengerSectionRows' => $passengerSectionRows,
            'businessSectionRows' => $businessSectionRows,
            'grandTotalFmt' => $grandTotalFmt,
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
            'coordName' => self::nzString($form['coordinator_name'] ?? null),
            'coordEmail' => self::nzString($form['coordinator_email'] ?? null),
            'coordPhone' => self::nzString($form['coordinator_phone'] ?? null),
            'tripType' => $tripTypeLabels[$tripTypeRaw] ?? $tripTypeRaw,
            'needPorters' => ! empty($form['need_porters']),
            'porterQty' => self::nzString($form['porter_qty'] ?? null),
            'porterCost' => ! empty($form['porter_cost'])
                ? number_format(self::parseMoney($form['porter_cost'] ?? null), 0, ',', '.').' đ'
                : '',
            'interprovincial' => ! empty($form['interprovincial']),
            'interprovincialCost' => ! empty($form['interprovincial_cost'])
                ? number_format(self::parseMoney($form['interprovincial_cost'] ?? null), 0, ',', '.').' đ'
                : '',
            'cargoExtraNotes' => self::nzString($form['cargo_extra_notes'] ?? null),
            'poCode' => self::nzString($dr->paper_reference),
            'g2Date' => $g2Date,
        ];
    }

    /**
     * @deprecated Use {@see buildPdfData()}
     *
     * @return array<string, mixed>
     */
    public static function forModel(DispatchRequest $dispatchRequest): array
    {
        return self::buildPdfData($dispatchRequest);
    }

    private static function basisLineFromForm(array $form): string
    {
        $ref = self::nzString($form['basis_ref'] ?? null);
        if ($ref !== '') {
            return $ref;
        }
        $basisFileName = self::nzString($form['basisFileName'] ?? null);

        return $basisFileName !== ''
            ? 'Đính kèm tệp «'.$basisFileName.'»'
            : '';
    }

    private static function resolveG2Date(DispatchRequest $dr): string
    {
        if ($dr->paper_received_at) {
            return Carbon::parse($dr->paper_received_at)->timezone(self::PDF_TZ)->format('d/m/Y');
        }
        if ($dr->status === 'approved' && $dr->updated_at) {
            return $dr->updated_at->timezone(self::PDF_TZ)->format('d/m/Y');
        }

        return '';
    }

    /**
     * @param  array<int, array<string, mixed>>  $cargoRows
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private static function collectCargoModels(array $cargoRows)
    {
        return collect($cargoRows)->filter(fn (array $r) => self::nzString($r['name'] ?? null) !== '');
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private static function collectPassengerModels(array $rows)
    {
        return collect($rows)->filter(fn (array $r) => self::isPassengerRowFilled($r));
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private static function collectBusinessModels(array $rows)
    {
        return collect($rows)->filter(fn (array $r) => self::isBusinessRowFilled($r));
    }

    /**
     * @param  array<string, mixed>  $r
     */
    private static function cargoRowToPdf(array $r): array
    {
        return [
            'name' => self::nzString($r['name'] ?? null),
            'qty' => self::fmtPdfQty($r['qty'] ?? null),
            'dim' => self::nzString($r['dimensions'] ?? null),
            'weight' => self::nzString($r['weight'] ?? null),
            'inotes' => self::nzString($r['item_notes'] ?? null),
            'puTime' => self::fmtPdfShortDatetime($r['pickup_at'] ?? null),
            'puPlace' => self::nzString($r['pickup_place'] ?? null),
            'puContact' => self::nzString($r['pickup_contact'] ?? null),
            'delTime' => self::fmtPdfShortDatetime($r['delivery_at'] ?? null),
            'delPlace' => self::nzString($r['delivery_place'] ?? null),
            'delContact' => self::nzString($r['delivery_contact'] ?? null),
            'transport' => self::nzString($r['transport_note'] ?? null),
            'cost' => self::formatCostCell(
                self::nzString($r['name'] ?? null),
                self::nzString($r['cost'] ?? null),
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $r
     */
    private static function passengerRowToPdf(array $r): array
    {
        $name = self::nzString($r['description'] ?? null);
        if ($name === '') {
            $name = self::nzString($r['name'] ?? null);
        }

        $qty = self::fmtPdfQty($r['guests'] ?? null);
        if ($qty === '') {
            $qty = self::fmtPdfQty($r['passenger_count'] ?? null);
        }

        $puPlace = self::nzString($r['pickup_place'] ?? null);
        if ($puPlace === '') {
            $puPlace = self::nzString($r['pickup'] ?? null);
        }
        $delPlace = self::nzString($r['dropoff_place'] ?? null);
        if ($delPlace === '') {
            $delPlace = self::nzString($r['dropoff'] ?? null);
        }

        return [
            'name' => $name,
            'qty' => $qty,
            'puTime' => self::fmtPdfShortDatetime($r['depart_at'] ?? null),
            'puPlace' => $puPlace,
            'delTime' => self::fmtPdfShortDatetime($r['return_at'] ?? null),
            'delPlace' => $delPlace,
            'puContact' => self::nzString($r['person_in_charge'] ?? null),
            'unitPrice' => self::fmtPdfVndSuffix($r['unit_price'] ?? null),
            'extraFee' => self::fmtPdfVndSuffix($r['extra_fee'] ?? null),
            'inotes' => self::nzString($r['notes'] ?? null),
        ];
    }

    /**
     * @param  array<string, mixed>  $r
     */
    private static function businessRowToPdf(array $r): array
    {
        return [
            'name' => self::nzString($r['description'] ?? null) ?: 'Công tác',
            'qty' => self::fmtPdfQty($r['guests'] ?? null),
            'dim' => self::nzString($r['waypoint'] ?? null),
            'inotes' => self::nzString($r['notes'] ?? null),
            'puTime' => self::fmtPdfShortDatetime($r['depart_at'] ?? null),
            'puPlace' => self::nzString($r['pickup'] ?? null),
            'delTime' => self::fmtPdfShortDatetime($r['return_at'] ?? null),
            'delPlace' => self::nzString($r['dropoff'] ?? null),
            'delContact' => self::nzString($r['other'] ?? null),
        ];
    }

    private static function resolveLogoDataUri(): string
    {
        foreach ([
            public_path('images/vas-logo.png'),
            public_path('images/logo/vas-logo.png'),
        ] as $path) {
            if (is_readable($path)) {
                $raw = @file_get_contents($path);
                if ($raw !== false) {
                    return 'data:image/png;base64,'.base64_encode($raw);
                }
            }
        }

        return '';
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

    private static function fmtPdfShortDatetime(mixed $v): string
    {
        $s = self::nzString(is_scalar($v) ? (string) $v : null);
        if ($s === '') {
            return '';
        }

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $s, $m)) {
            return sprintf('%02d/%02d/%s', (int) $m[3], (int) $m[2], $m[1]);
        }

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})[T\s](\d{2}):(\d{2})/', $s, $m)
            && ! preg_match('/Z$|[+-]\d{2}:?\d{2}$/', $s)) {
            return sprintf('%02d/%02d %s:%s', (int) $m[3], (int) $m[2], $m[4], $m[5]);
        }

        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})(?:\s+(\d{1,2}):(\d{2}))?$/', $s, $m)) {
            if (isset($m[4], $m[5])) {
                return sprintf('%02d/%02d %02d:%s', (int) $m[1], (int) $m[2], (int) $m[4], $m[5]);
            }

            return sprintf('%02d/%02d/%s', (int) $m[1], (int) $m[2], $m[3]);
        }

        try {
            return Carbon::parse($s)->timezone(self::PDF_TZ)->format('d/m H:i');
        } catch (\Throwable) {
            return $s;
        }
    }

    private static function fmtPdfQty(mixed $v): string
    {
        $s = self::nzString(is_scalar($v) ? (string) $v : null);
        if ($s === '') {
            return '';
        }
        if (is_numeric($s)) {
            return (string) (int) round((float) $s);
        }

        return $s;
    }

    private static function fmtPdfVndSuffix(mixed $v): string
    {
        $n = self::parseMoney($v);

        return $n > 0 ? number_format($n, 0, ',', '.').' đ' : '';
    }

    private static function fmtDateStr(?string $d): string
    {
        $s = self::nzString($d);
        if ($s === '') {
            return '';
        }

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $s, $m)) {
            return sprintf('%02d/%02d/%s', (int) $m[3], (int) $m[2], $m[1]);
        }

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $s, $m)
            && ! preg_match('/Z$|[+-]\d{2}:?\d{2}$/', $s)) {
            return sprintf('%02d/%02d/%s', (int) $m[3], (int) $m[2], $m[1]);
        }

        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $s, $m)) {
            return sprintf('%02d/%02d/%s', (int) $m[1], (int) $m[2], $m[3]);
        }

        try {
            return Carbon::parse($s)->timezone(self::PDF_TZ)->format('d/m/Y');
        } catch (\Throwable) {
            return $s;
        }
    }

    public static function parseMoney(mixed $v): int
    {
        if ($v === null || $v === '') {
            return 0;
        }
        if (is_int($v)) {
            return $v;
        }
        if (is_float($v)) {
            return (int) round($v);
        }

        $s = trim((string) $v);
        $s = preg_replace('/[^\d,.-]/u', '', $s) ?? '';
        if ($s === '' || $s === '-') {
            return 0;
        }
        if (preg_match('/^\d{1,3}(\.\d{3})+$/', $s)) {
            return (int) str_replace('.', '', $s);
        }
        if (preg_match('/^\d+$/', $s)) {
            return (int) $s;
        }

        $s = str_replace(['.', ' ', ','], '', $s);
        if ($s === '' || ! is_numeric($s)) {
            return 0;
        }

        return (int) round((float) $s);
    }

    /**
     * @param  array<string, mixed>  $r
     */
    private static function isPassengerRowFilled(array $r): bool
    {
        if (self::nzString($r['pickup_place'] ?? null) || self::nzString($r['dropoff_place'] ?? null)) {
            return true;
        }
        if (self::nzString($r['pickup'] ?? null) || self::nzString($r['dropoff'] ?? null)) {
            return true;
        }
        if (self::nzString($r['description'] ?? null)) {
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
        if (self::nzString($r['notes'] ?? null) || self::nzString($r['description'] ?? null)) {
            return true;
        }
        $g = self::nzString($r['guests'] ?? null);

        return $g !== '' && $g !== '1';
    }
}
