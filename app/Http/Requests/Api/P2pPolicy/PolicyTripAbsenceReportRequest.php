<?php

namespace App\Http\Requests\Api\P2pPolicy;

use App\Http\Requests\Api\ApiFormRequest;

class PolicyTripAbsenceReportRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['policy_trip.view']);
    }

    public function rules(): array
    {
        return [
            'week_start' => ['required', 'date'],
            'time_slot' => ['nullable', 'in:morning,afternoon'],
            'route_id' => ['nullable', 'integer', 'exists:routes,id'],
            'class' => ['nullable', 'string', 'max:64'],
        ];
    }
}
