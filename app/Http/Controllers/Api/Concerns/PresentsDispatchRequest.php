<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\DispatchRequest;
use App\Models\DispatchSetting;

trait PresentsDispatchRequest
{
    /**
     * @return array<string, mixed>
     */
    protected function presentDispatchRequest(DispatchRequest $dispatchRequest): array
    {
        $dispatchRequest->loadMissing('dispatchRequestTemplate.dispatchPackage');

        $arr = $dispatchRequest->toArray();
        $arr['threshold_hours'] = DispatchSetting::urgentThresholdHoursForTripType((string) $dispatchRequest->trip_type);
        $arr['is_urgent_auto'] = $dispatchRequest->isUrgentAuto();

        if ($dispatchRequest->dispatch_request_template_id !== null) {
            $pkg = $dispatchRequest->dispatchRequestTemplate?->dispatchPackage;
            $arr['dispatch_package_sessions'] = null;
            $arr['dispatch_package_cost_alert'] = null;
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
            }
        }

        return $arr;
    }
}
