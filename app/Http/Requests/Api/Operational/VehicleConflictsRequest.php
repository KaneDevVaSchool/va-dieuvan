<?php

namespace App\Http\Requests\Api\Operational;

use App\Http\Requests\Api\ApiFormRequest;

class VehicleConflictsRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAllOf(['trip.assign']);
    }

    public function rules(): array
    {
        return [
            'trip_id' => ['nullable', 'integer', 'min:1'],
            'exclude_trip' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
