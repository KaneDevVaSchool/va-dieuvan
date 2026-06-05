<?php

namespace App\Http\Requests\Api\Reports;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DriverFrequencyReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('report.view') ?? false;
    }

    public function rules(): array
    {
        $currentYear = (int) now()->format('Y');

        return [
            'year'          => ['nullable', 'integer', 'min:2020', 'max:'.($currentYear + 1)],
            'quarter'       => ['nullable', 'string', Rule::in(['q1', 'q2', 'q3', 'q4'])],
            'driver_id'     => ['nullable', 'integer', 'min:1'],
            'vehicle_id'    => ['nullable', 'integer', 'min:1'],
            'vehicle_plate' => ['nullable', 'string', 'max:32'],
            'trip_type'     => ['nullable', 'string', 'in:point_to_point,business,cargo,door_to_door'],
        ];
    }
}
