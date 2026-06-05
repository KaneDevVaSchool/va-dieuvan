<?php

namespace App\Http\Requests\Api\Reports;

use Illuminate\Foundation\Http\FormRequest;

class TripCostReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('report.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'status'     => ['nullable', 'string'],
            'type'       => ['nullable', 'string'],
            'trip_type'  => ['nullable', 'string', 'in:point_to_point,business,cargo,door_to_door'],
            'trip_id'    => ['nullable', 'integer', 'min:1'],
            'from'       => ['nullable', 'date'],
            'to'         => ['nullable', 'date', 'after_or_equal:from'],
            'provider'   => ['nullable', 'string'],
            'fleet_mode' => ['nullable', 'string', 'in:internal,vendor_hire,taxi,unspecified'],
        ];
    }
}
