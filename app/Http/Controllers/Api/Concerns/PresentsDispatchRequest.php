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
            if ($pkg !== null) {
                $arr['dispatch_package_sessions'] = [
                    'dispatch_package_id' => $pkg->id,
                    'total_sessions' => (int) $pkg->total_sessions,
                    'sessions_used' => (int) $pkg->sessions_used,
                    'sessions_remaining' => $pkg->remainingSessions(),
                    'alert_when_remaining_sessions' => (int) $pkg->alert_when_remaining_sessions,
                ];
            }
        }

        return $arr;
    }
}
