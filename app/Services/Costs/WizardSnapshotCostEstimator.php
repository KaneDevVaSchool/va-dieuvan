<?php

namespace App\Services\Costs;

use App\Models\DispatchRequest;

/**
 * Tổng dự toán từ wizard_snapshot — khớp logic UI (TripDetailView / useRequestCostEstimate).
 */
class WizardSnapshotCostEstimator
{
    public const PROVISION_TYPE = 'wizard_estimate';

    public function estimatedTotalVnd(DispatchRequest $dispatchRequest): float
    {
        $snap = is_array($dispatchRequest->wizard_snapshot) ? $dispatchRequest->wizard_snapshot : [];
        $fromSnap = $this->totalFromSnapshot($snap);
        if ($fromSnap > 0) {
            return round($fromSnap, 2);
        }

        $service = $dispatchRequest->service_price;
        if ($service !== null && is_numeric($service) && (float) $service > 0) {
            return round((float) $service, 2);
        }

        return 0.0;
    }

    /**
     * @param  array<string, mixed>  $snap
     */
    public function totalFromSnapshot(array $snap): float
    {
        $form = $snap['form'] ?? [];
        $form = is_array($form) ? $form : [];

        $extras = 0.0;
        if (! empty($form['need_porters'])) {
            $extras += $this->parseMoney($form['porter_cost'] ?? null);
        }
        if (! empty($form['interprovincial'])) {
            $extras += $this->parseMoney($form['interprovincial_cost'] ?? null);
        }
        if (! empty($form['e1_use_3plus_days'])) {
            $extras += $this->parseMoney($form['e1_extra_cost'] ?? null);
        }
        if (! empty($form['e2_door_pickup'])) {
            $extras += $this->parseMoney($form['e2_door_cost'] ?? null);
        }
        if (! empty($form['e2_driver_self'])) {
            $extras += $this->parseMoney($form['e2_driver_self_cost'] ?? null);
        }
        if (! empty($form['e2_after_21h'])) {
            $extras += $this->parseMoney($form['e2_after_21h_cost'] ?? null);
        }

        $rowTotal = fn (array $row): float => $this->parseMoney($row['unit_price'] ?? null)
            + $this->parseMoney($row['extra_fee'] ?? null);

        $totalPass = 0.0;
        foreach ($snap['passengerRows'] ?? [] as $row) {
            if (is_array($row)) {
                $totalPass += $rowTotal($row);
            }
        }

        $totalBus = 0.0;
        foreach ($snap['businessRows'] ?? [] as $row) {
            if (is_array($row)) {
                $totalBus += $rowTotal($row);
            }
        }

        $cargoCosts = 0.0;
        foreach ($snap['cargoRows'] ?? [] as $row) {
            if (is_array($row)) {
                $cargoCosts += $this->parseMoney($row['cost'] ?? null);
            }
        }

        return $extras + $totalPass + $totalBus + $cargoCosts;
    }

    private function parseMoney(mixed $v): float
    {
        if ($v === null || $v === '') {
            return 0.0;
        }
        if (is_numeric($v)) {
            return max(0.0, (float) $v);
        }
        $s = preg_replace('/[^\d.-]/', '', (string) $v) ?? '';
        if ($s === '' || ! is_numeric($s)) {
            return 0.0;
        }

        return max(0.0, (float) $s);
    }
}
