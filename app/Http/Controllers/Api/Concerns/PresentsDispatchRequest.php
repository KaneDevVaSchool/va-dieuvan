<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\DispatchRequest;
use App\Models\DispatchSetting;
use App\Services\RecurringDispatch\RecurringBudgetAlertService;

trait PresentsDispatchRequest
{
    /**
     * @return array<string, mixed>
     */
    protected function presentDispatchRequest(DispatchRequest $dispatchRequest, bool $includeWizardSnapshot = false): array
    {
        $dispatchRequest->loadMissing('dispatchRequestTemplate.dispatchPackage');

        if ($includeWizardSnapshot) {
            $dispatchRequest->makeVisible(['wizard_snapshot']);
        }

        $arr = $dispatchRequest->toArray();
        $arr['threshold_hours'] = DispatchSetting::urgentThresholdHoursForTripType((string) $dispatchRequest->trip_type);
        $arr['is_urgent_auto'] = $dispatchRequest->isUrgentAuto();

        $arr['recurring_plan_label'] = null;
        if ($dispatchRequest->dispatch_request_template_id !== null) {
            $template = $dispatchRequest->dispatchRequestTemplate;
            $planRaw = $template?->dispatchPackage?->label
                ?? data_get($template?->wizard_snapshot, 'form.plan_name');
            if (is_string($planRaw) && trim($planRaw) !== '') {
                $arr['recurring_plan_label'] = trim($planRaw);
            }

            $pkg = $template?->dispatchPackage;
            $arr['dispatch_package_sessions'] = null;
            $arr['dispatch_package_cost_alert'] = null;
            $arr['dispatch_package_budget_alert'] = null;
            if ($pkg !== null) {
                $total = (int) $pkg->total_sessions;
                $used = (int) $pkg->sessions_used;
                $remaining = $pkg->remainingSessions();
                $threshold = max(0, (int) $pkg->alert_when_remaining_sessions);
                $exceeded = $total > 0 && $used >= $total;
                $warn = ! $exceeded && $remaining <= $threshold;

                $arr['dispatch_package_sessions'] = [
                    'dispatch_package_id' => $pkg->id,
                    'total_sessions' => $total,
                    'sessions_used' => $used,
                    'sessions_remaining' => $remaining,
                    'alert_when_remaining_sessions' => $threshold,
                ];

                if ($exceeded || $warn) {
                    $label = (string) ($pkg->label !== null && $pkg->label !== '' ? $pkg->label : $pkg->trip_type);
                    $arr['dispatch_package_cost_alert'] = [
                        'severity' => $exceeded ? 'exceeded' : 'warning',
                        'package_label' => $label,
                        'sessions_used' => $used,
                        'total_sessions' => $total,
                        'sessions_remaining' => $remaining,
                        'threshold_sessions' => $threshold,
                    ];
                }

                $budgetAlert = app(RecurringBudgetAlertService::class)->computeMonthlyAlertForPackage(
                    $pkg,
                    $dispatchRequest->depart_at ? \Illuminate\Support\Carbon::parse($dispatchRequest->depart_at) : null,
                );
                if ($budgetAlert !== null) {
                    $arr['dispatch_package_budget_alert'] = $budgetAlert;
                }
            }
        }

        return $arr;
    }
}
