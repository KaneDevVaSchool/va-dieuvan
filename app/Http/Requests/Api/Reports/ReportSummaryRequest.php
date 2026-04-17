<?php

namespace App\Http\Requests\Api\Reports;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class ReportSummaryRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAllOf(['report.view']);
    }

    protected function prepareForValidation(): void
    {
        $merge = [];
        if ($this->has('is_urgent')) {
            $v = $this->input('is_urgent');
            if ($v === 'true' || $v === '1' || $v === 1 || $v === true) {
                $merge['is_urgent'] = true;
            } elseif ($v === 'false' || $v === '0' || $v === 0 || $v === false || $v === '') {
                $merge['is_urgent'] = null;
            }
        }
        foreach (['trip_type', 'source_channel', 'paper_status', 'trip_status', 'fleet_mode'] as $key) {
            if ($this->has($key) && $this->input($key) === '') {
                $merge[$key] = null;
            }
        }
        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'trip_type' => ['nullable', Rule::in(['door_to_door', 'point_to_point', 'business', 'cargo'])],
            'source_channel' => ['nullable', Rule::in(['portal', 'zalo', 'paper'])],
            'paper_status' => ['nullable', Rule::in(['pending', 'received', 'digitally_signed'])],
            'is_urgent' => ['nullable', 'boolean'],
            'trip_status' => ['nullable', Rule::in([
                'pending', 'approved', 'assigned', 'driver_confirmed', 'in_progress', 'completed', 'cancelled', 'incident',
            ])],
            'fleet_mode' => ['nullable', Rule::in(['internal', 'vendor_hire', 'taxi', 'unspecified'])],
        ];
    }
}
