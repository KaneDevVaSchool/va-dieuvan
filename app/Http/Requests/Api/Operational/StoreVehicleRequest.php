<?php

namespace App\Http\Requests\Api\Operational;

use App\Http\Requests\Api\ApiFormRequest;

class StoreVehicleRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['resource.vehicle.manage']);
    }

    public function rules(): array
    {
        return [
            'license_plate' => ['required', 'string', 'max:64', 'unique:vehicles,license_plate'],
            'type' => ['nullable', 'string', 'max:255'],
            'seat_count' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'payload_kg' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'string', 'in:ready,in_use,maintenance,broken'],
            'odometer_km' => ['nullable', 'integer', 'min:0'],
            'inspection_expires_at' => ['nullable', 'date'],
            'insurance_expires_at' => ['nullable', 'date'],
            'default_driver_id' => ['nullable', 'integer', 'exists:drivers,id'],
        ];
    }
}
