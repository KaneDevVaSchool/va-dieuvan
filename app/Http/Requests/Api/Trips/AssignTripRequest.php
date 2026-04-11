<?php

namespace App\Http\Requests\Api\Trips;

use App\Http\Requests\Api\ApiFormRequest;

class AssignTripRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAllOf(['trip.assign']);
    }

    public function rules(): array
    {
        return [
            'lock_version' => ['required', 'integer', 'min:0'],
            'vehicle_id' => ['nullable', 'integer', 'min:1'],
            'driver_id' => ['nullable', 'integer', 'min:1'],
            'transport_provider_id' => ['nullable', 'integer', 'min:1'],
            'external_vehicle_ref' => ['nullable', 'string', 'max:255'],
            'external_driver_ref' => ['nullable', 'string', 'max:255'],
        ];
    }
}
