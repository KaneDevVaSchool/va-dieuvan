<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\ReadDispatchFormSettingsRequest;
use App\Http\Requests\Api\Admin\ShowDispatchSettingRequest;
use App\Http\Requests\Api\Admin\UpdateDispatchSettingRequest;
use App\Models\DispatchSetting;
use Illuminate\Http\JsonResponse;

class DispatchSettingController extends Controller
{
    use ApiResponses;

    /** Ngưỡng hiển thị trên form tạo yêu cầu (dispatcher). */
    public function indexForWizard(ReadDispatchFormSettingsRequest $request): JsonResponse
    {
        return $this->ok([
            'passenger_urgent_threshold_hours' => DispatchSetting::passengerUrgentThresholdHours(),
            'cargo_urgent_threshold_hours' => DispatchSetting::cargoUrgentThresholdHours(),
            'reference_pricing_url' => DispatchSetting::referencePricingUrl(),
        ]);
    }

    public function show(ShowDispatchSettingRequest $request): JsonResponse
    {
        $row = DispatchSetting::singletonRow();
        if (! $row) {
            return $this->ok($this->defaultSettingShape());
        }

        return $this->ok($row);
    }

    public function update(UpdateDispatchSettingRequest $request): JsonResponse
    {
        $validated = $request->validated();

        /** @var DispatchSetting $setting */
        $setting = DispatchSetting::query()->firstOrNew([]);
        if (! $setting->exists) {
            $setting->passenger_urgent_threshold_hours = (int) config('dispatch.passenger_urgent_threshold_hours');
            $setting->cargo_urgent_threshold_hours = (int) config('dispatch.cargo_urgent_threshold_hours');
            $setting->save();
        }
        $setting->update([
            'passenger_urgent_threshold_hours' => (int) $validated['passenger_urgent_threshold_hours'],
            'cargo_urgent_threshold_hours' => (int) $validated['cargo_urgent_threshold_hours'],
            'reference_pricing_url' => isset($validated['reference_pricing_url'])
                ? ($validated['reference_pricing_url'] !== null && trim((string) $validated['reference_pricing_url']) !== ''
                    ? trim((string) $validated['reference_pricing_url'])
                    : null)
                : $setting->reference_pricing_url,
        ]);

        return $this->ok($setting->fresh());
    }

    /**
     * @return array{passenger_urgent_threshold_hours: int, cargo_urgent_threshold_hours: int, reference_pricing_url: null}
     */
    private function defaultSettingShape(): array
    {
        return [
            'passenger_urgent_threshold_hours' => (int) config('dispatch.passenger_urgent_threshold_hours'),
            'cargo_urgent_threshold_hours' => (int) config('dispatch.cargo_urgent_threshold_hours'),
            'reference_pricing_url' => null,
        ];
    }
}
