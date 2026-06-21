<?php

namespace App\Services\DispatchRequests;

use App\Models\DispatchRequest;
use Illuminate\Support\Carbon;

final class DispatchRequestMailPresenter
{
    private const DEPART_TZ = 'Asia/Ho_Chi_Minh';

    public static function referenceCode(DispatchRequest $dr): string
    {
        $d = $dr->created_at ? ($dr->created_at instanceof Carbon ? $dr->created_at : Carbon::parse($dr->created_at)) : now();
        $d = $d->timezone(self::DEPART_TZ);

        return sprintf('REQ-%s%s-%s', $d->format('Y'), $d->format('m'), str_pad((string) $dr->id, 3, '0', STR_PAD_LEFT));
    }

    /**
     * Resolve dispatch_request.id from a list-search term (full ref code or legacy REQ-{id}).
     */
    public static function parseReferenceCodeSearchId(string $term): ?int
    {
        $term = trim($term);
        if ($term === '') {
            return null;
        }

        if (preg_match('/^REQ-(\d{6})-(\d+)$/i', $term, $m)) {
            $id = (int) $m[2];

            return $id > 0 ? $id : null;
        }

        if (preg_match('/^REQ-?(\d+)$/i', $term, $m)) {
            $id = (int) $m[1];

            return $id > 0 ? $id : null;
        }

        return null;
    }

    public static function tripTypeLabelVi(string $t): string
    {
        return match ($t) {
            'cargo' => 'Hàng hoá',
            'business' => 'Xe công tác',
            'point_to_point' => 'Điểm — điểm',
            'door_to_door' => 'Đưa đón (D2D)',
            default => $t !== '' ? $t : '—',
        };
    }

    /** @param  mixed  $amount */
    public static function moneyVnd($amount): string
    {
        if ($amount === null) {
            return '—';
        }
        try {
            $n = round((float) $amount);

            return number_format($n, 0, ',', '.').' đ';
        } catch (\Throwable) {
            return '—';
        }
    }

    /**
     * @return array<string, mixed>
     */
    public static function buildRequestSummary(DispatchRequest $dr): array
    {
        $form = self::formFromSnapshot($dr);
        $requesterUnit = trim((string) ($form['requester_unit'] ?? ''));
        $requesterName = trim((string) ($dr->requester?->name ?? ''));
        $requesterLine = $requesterUnit !== ''
            ? $requesterName.' — '.$requesterUnit
            : $requesterName;
        $coordinatorLine = trim((string) ($form['coordinator_name'] ?? ''));

        return [
            'requestRefCode' => self::referenceCode($dr),
            'tripTypeLabel' => self::tripTypeLabelVi($dr->trip_type ?? ''),
            'requesterLine' => $requesterLine !== '' ? $requesterLine : '—',
            'purposeLine' => trim((string) ($form['purpose'] ?? '')) ?: '—',
            'leaderLine' => $coordinatorLine !== '' ? $coordinatorLine : '—',
            'routeLine' => trim(($dr->origin ?? '').' → '.($dr->destination ?? '')),
            'timeLineDepart' => self::formatDateTimeVi($dr->depart_at) ?: '—',
            'timeLineArrive' => self::formatDateTimeVi($dr->arrive_by) ?: '—',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function buildPriceSummary(DispatchRequest $dr): array
    {
        $snap = is_array($dr->wizard_snapshot) ? $dr->wizard_snapshot : [];
        $isCargo = ($dr->trip_type ?? '') === 'cargo';
        $unitTotal = $isCargo
            ? self::sumRowMoneyField($snap, 'cargoRows', 'cost')
            : self::sumRowMoneyField($snap, 'businessRows', 'unit_price')
                + self::sumRowMoneyField($snap, 'passengerRows', 'unit_price');
        $extrasTotal = $isCargo ? 0.0 : self::sumExtraFeesFromSnapshot($snap);
        $grand = round((float) ($dr->service_price ?? 0), 2);
        $showPriceBreakdown = ! $isCargo && ($unitTotal > 0 || $extrasTotal > 0);

        return [
            'isCargoTrip' => $isCargo,
            'showPriceBreakdown' => $showPriceBreakdown,
            'showExtraLine' => $showPriceBreakdown && $extrasTotal > 0,
            'unitPriceTotalFmt' => self::moneyVnd($unitTotal),
            'extraFeesFmt' => self::moneyVnd($extrasTotal),
            'grandTotalFmt' => self::moneyVnd($grand),
        ];
    }

    /**
     * @return array{hasDeadlineNotice: bool, deadlineNotice: string}
     */
    public static function buildDeadlineNotice(DispatchRequest $dr): array
    {
        $deadlineNotice = '';
        try {
            if ($dr->depart_at) {
                $base = $dr->depart_at instanceof Carbon ? $dr->depart_at->copy() : Carbon::parse($dr->depart_at);
                $deadline = $base->timezone(self::DEPART_TZ)->copy()->subDay()->setTime(17, 0, 0);
                $deadlineNotice = sprintf(
                    'Trước 17:00 ngày %s.',
                    $deadline->format('d/m/Y')
                );
            }
        } catch (\Throwable) {
            $deadlineNotice = '';
        }

        return [
            'hasDeadlineNotice' => $deadlineNotice !== '',
            'deadlineNotice' => $deadlineNotice,
        ];
    }

    public static function detailUrlForRequest(DispatchRequest $dr, string $pathPrefix): string
    {
        $prefix = trim($pathPrefix, '/');

        return rtrim(config('app.url'), '/').'/'.$prefix.'/'.$dr->id;
    }

    public static function helpdesk(): string
    {
        return trim((string) config('dispatch.mail_helpdesk'));
    }

    /**
     * @return array<string, mixed>
     */
    private static function formFromSnapshot(DispatchRequest $dr): array
    {
        $snap = $dr->wizard_snapshot ?? [];
        if (isset($snap['form']) && is_array($snap['form'])) {
            return $snap['form'];
        }

        return [];
    }

    /**
     * @param  mixed  $value
     */
    private static function formatDateTimeVi($value): string
    {
        if (! $value) {
            return '';
        }
        try {
            $d = $value instanceof Carbon ? $value->copy() : Carbon::parse($value);

            return $d->timezone(self::DEPART_TZ)->format('d/m/Y').' '.$d->timezone(self::DEPART_TZ)->format('H:i');
        } catch (\Throwable) {
            return '';
        }
    }

    private static function sumExtraFeesFromSnapshot(array $snap): float
    {
        return round(
            self::sumRowMoneyField($snap, 'businessRows', 'extra_fee')
                + self::sumRowMoneyField($snap, 'passengerRows', 'extra_fee'),
            2,
        );
    }

    private static function sumRowMoneyField(array $snap, string $rowsKey, string $field): float
    {
        $rows = $snap[$rowsKey] ?? null;
        if (! is_array($rows)) {
            return 0.0;
        }

        $total = 0.0;
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $v = $row[$field] ?? null;
            if ($v === null || $v === '') {
                continue;
            }
            if (is_numeric($v)) {
                $total += (float) $v;
            }
        }

        return round($total, 2);
    }
}
