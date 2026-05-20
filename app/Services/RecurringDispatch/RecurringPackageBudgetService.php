<?php

namespace App\Services\RecurringDispatch;

use App\Models\DispatchPackage;
use App\Models\DispatchRequest;
use App\Models\DispatchRequestTemplate;

class RecurringPackageBudgetService
{
    /** @var array<int, array<string, mixed>|null> */
    private static array $summarizeCache = [];

    public static function parseVndAmount(mixed $raw): ?float
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        $s = preg_replace('/[^\d]/', '', (string) $raw);
        if ($s === '') {
            return null;
        }
        $n = (float) $s;

        return $n > 0 ? $n : null;
    }

    /**
     * Ngân sách gói CLB (cột monthly_budget) so với tổng service_price đã điền trên các phiếu.
     *
     * @return array<string, mixed>|null
     */
    public function summarize(DispatchPackage $package): ?array
    {
        $id = (int) $package->id;
        if (array_key_exists($id, self::$summarizeCache)) {
            return self::$summarizeCache[$id];
        }

        $budget = $package->monthly_budget !== null ? (float) $package->monthly_budget : 0.0;
        if ($budget <= 0) {
            $budget = $this->inferBudgetFromTemplates($package) ?? 0.0;
        }
        if ($budget <= 0) {
            self::$summarizeCache[$id] = null;

            return null;
        }

        $templateIds = DispatchRequestTemplate::query()
            ->where('dispatch_package_id', $package->id)
            ->pluck('id');

        $used = 0.0;
        if ($templateIds->isNotEmpty()) {
            $used = (float) DispatchRequest::query()
                ->whereIn('dispatch_request_template_id', $templateIds)
                ->whereNotIn('status', ['cancelled'])
                ->whereNotNull('service_price')
                ->sum('service_price');
        }

        $remaining = max(0.0, $budget - $used);
        $usedPct = $budget > 0 ? ($used / $budget) * 100 : 0.0;
        $remainingPct = $budget > 0 ? ($remaining / $budget) * 100 : 0.0;

        $severity = null;
        if ($used >= $budget) {
            $severity = 'exceeded';
        } elseif ($remainingPct <= 10.0 || $remaining <= max($budget * 0.1, 500_000)) {
            $severity = 'warning';
        }

        $label = (string) ($package->label !== null && $package->label !== '' ? $package->label : $package->trip_type);

        $out = [
            'dispatch_package_id' => $id,
            'package_label' => $label,
            'budget' => round($budget, 2),
            'used' => round($used, 2),
            'remaining' => round($remaining, 2),
            'used_percent' => round($usedPct, 1),
            'severity' => $severity,
        ];

        self::$summarizeCache[$id] = $out;

        return $out;
    }

    private function inferBudgetFromTemplates(DispatchPackage $package): ?float
    {
        $template = DispatchRequestTemplate::query()
            ->where('dispatch_package_id', $package->id)
            ->orderBy('id')
            ->first(['wizard_snapshot']);

        if ($template === null) {
            return null;
        }

        $raw = data_get($template->wizard_snapshot, 'form.estimated_vehicle_cost');

        return self::parseVndAmount($raw);
    }
}
