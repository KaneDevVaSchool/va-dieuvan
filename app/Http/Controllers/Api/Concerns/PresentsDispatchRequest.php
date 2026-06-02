<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\DispatchRequest;
use App\Models\DispatchSetting;
use App\Services\RecurringDispatch\RecurringBudgetAlertService;
use App\Services\RecurringDispatch\RecurringPackageBudgetService;

trait PresentsDispatchRequest
{
    use PresentsSignedDocuments;

    /**
     * @return array<string, mixed>
     */
    protected function presentDispatchRequest(DispatchRequest $dispatchRequest, bool $includeWizardSnapshot = false): array
    {
        $dispatchRequest->loadMissing([
            'dispatchRequestTemplate.dispatchPackage',
            'currentSignedVersion.attachment',
            'currentSignedVersion.uploader',
        ]);

        if ($includeWizardSnapshot) {
            $dispatchRequest->makeVisible(['wizard_snapshot']);
        }

        $arr = $dispatchRequest->toArray();
        $arr['price_filled_by_user'] = null;
        if ($dispatchRequest->relationLoaded('priceFiller') && $dispatchRequest->priceFiller !== null) {
            $u = $dispatchRequest->priceFiller;
            $arr['price_filled_by_user'] = [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
            ];
        }

        $arr['cloned_from_summary'] = null;
        if ($dispatchRequest->cloned_from_id && $dispatchRequest->relationLoaded('clonedFrom') && $dispatchRequest->clonedFrom) {
            $src = $dispatchRequest->clonedFrom;
            $arr['cloned_from_summary'] = [
                'id' => $src->id,
                'status' => $src->status,
                'origin' => $src->origin,
                'destination' => $src->destination,
                'created_at' => $src->created_at?->toIso8601String(),
            ];
        }

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
            $arr['dispatch_package_budget_usage'] = null;
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

                $usage = app(RecurringPackageBudgetService::class)->summarize($pkg);
                if ($usage !== null) {
                    $arr['dispatch_package_budget_usage'] = $usage;
                }
            }
        }

        return array_merge($arr, $this->presentSignedDocumentBlock($dispatchRequest, false));
    }
}
