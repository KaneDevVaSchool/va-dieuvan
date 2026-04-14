<?php

namespace App\Http\Requests\Api\Operational;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['resource.vehicle.manage']);
    }

    public function rules(): array
    {
        return [
            'license_plate' => [
                'required',
                'string',
                'max:64',
                Rule::unique('vehicles', 'license_plate')->whereNull('deleted_at'),
            ],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'frame_engine_number' => ['nullable', 'string', 'max:20000'],
            'type' => ['nullable', 'string', 'max:255'],
            'year_manufactured' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'purchased_at' => ['nullable', 'date'],
            'usage_expires_year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'seat_count' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'payload_kg' => ['nullable', 'integer', 'min:0'],
            'insurance_provider' => ['nullable', 'string', 'max:64'],
            'insurance_policy_note' => ['nullable', 'string', 'max:20000'],
            'status' => ['nullable', 'string', 'in:ready,in_use,maintenance,broken'],
            'odometer_km' => ['nullable', 'integer', 'min:0'],
            'inspection_expires_at' => ['nullable', 'date'],
            'insurance_expires_at' => ['nullable', 'date'],
            'road_fee_expires_at' => ['nullable', 'date'],
            'registration_cycle_note' => ['nullable', 'string', 'max:255'],
            'last_maintenance_at' => ['nullable', 'date'],
            'maintenance_schedule_note' => ['nullable', 'string', 'max:20000'],
            'caretaker_name' => ['nullable', 'string', 'max:255'],
            'caretaker_phone' => ['nullable', 'string', 'max:32'],
            'notes' => ['nullable', 'string', 'max:20000'],
            'default_driver_id' => ['nullable', 'integer', 'exists:drivers,id'],
        ];
    }
}
