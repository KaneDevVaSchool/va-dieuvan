<?php

namespace App\Http\Requests\Api\TransportProgram;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateTpProgramRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['tp_program.manage']);
    }

    public function rules(): array
    {
        $id = $this->route('tpProgram')?->id;

        return [
            'code' => ['sometimes', 'string', 'max:30', Rule::unique('tp_programs', 'code')->ignore($id)],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'origin_name' => ['nullable', 'string', 'max:255'],
            'destination_name' => ['nullable', 'string', 'max:255'],
            'departure_time' => ['sometimes', 'date_format:H:i'],
            'return_time' => ['nullable', 'date_format:H:i'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date', 'after_or_equal:start_date'],
            'runs_on' => ['sometimes', 'array'],
            'runs_on.*' => ['in:mon,tue,wed,thu,fri,sat,sun'],
            'excluded_dates' => ['nullable', 'array'],
            'excluded_dates.*' => ['date'],
            'extra_dates' => ['nullable', 'array'],
            'extra_dates.*' => ['date'],
            'default_driver_id' => ['nullable', 'integer', 'exists:drivers,id'],
            'default_vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'cost_per_trip' => ['nullable', 'numeric', 'min:0'],
            'cost_notes' => ['nullable', 'string'],
            'responsible_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
