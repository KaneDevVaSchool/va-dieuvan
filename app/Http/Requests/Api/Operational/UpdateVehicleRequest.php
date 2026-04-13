<?php

namespace App\Http\Requests\Api\Operational;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateVehicleRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['resource.vehicle.manage', 'trip.assign']);
    }

    public function rules(): array
    {
        $user = $this->user();
        if ($user && ($user->isSuperAdmin() || $user->can('resource.vehicle.manage'))) {
            /** @var \App\Models\Vehicle|null $vehicle */
            $vehicle = $this->route('vehicle');

            return [
                'license_plate' => [
                    'sometimes',
                    'string',
                    'max:64',
                    Rule::unique('vehicles', 'license_plate')->ignore($vehicle?->id),
                ],
                'type' => ['nullable', 'string', 'max:255'],
                'seat_count' => ['nullable', 'integer', 'min:0', 'max:65535'],
                'payload_kg' => ['nullable', 'integer', 'min:0', 'max:4294967295'],
                'status' => ['sometimes', 'string', 'in:ready,in_use,maintenance,broken'],
                'odometer_km' => ['nullable', 'integer', 'min:0'],
                'inspection_expires_at' => ['nullable', 'date'],
                'insurance_expires_at' => ['nullable', 'date'],
                'default_driver_id' => ['nullable', 'integer', 'exists:drivers,id'],
            ];
        }

        return [
            'default_driver_id' => ['nullable', 'integer', 'exists:drivers,id'],
        ];
    }
}
