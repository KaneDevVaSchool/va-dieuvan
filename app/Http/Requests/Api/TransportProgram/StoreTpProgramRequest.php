<?php

namespace App\Http\Requests\Api\TransportProgram;

use App\Http\Requests\Api\ApiFormRequest;

class StoreTpProgramRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['tp_program.manage']);
    }

    public function rules(): array
    {
        return [
            'code' => ['nullable', 'string', 'max:30', 'unique:tp_programs,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'origin_name' => ['nullable', 'string', 'max:255'],
            'destination_name' => ['nullable', 'string', 'max:255'],
            'origin_location_id' => ['nullable', 'integer'],
            'destination_location_id' => ['nullable', 'integer'],
            'departure_time' => ['required', 'date_format:H:i'],
            'return_time' => ['nullable', 'date_format:H:i'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'runs_on' => ['nullable', 'array'],
            'runs_on.*' => ['in:mon,tue,wed,thu,fri,sat,sun'],
            'excluded_dates' => ['nullable', 'array'],
            'excluded_dates.*' => ['date'],
            'extra_dates' => ['nullable', 'array'],
            'extra_dates.*' => ['date'],
            'default_driver_id' => ['nullable', 'integer', 'exists:drivers,id'],
            'default_vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'cost_per_trip' => ['nullable', 'numeric', 'min:0'],
            'cost_currency' => ['nullable', 'string', 'size:3'],
            'cost_notes' => ['nullable', 'string'],
            'responsible_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
