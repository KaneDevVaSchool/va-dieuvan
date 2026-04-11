<?php

namespace App\Http\Requests\Api\D2D;

use App\Http\Requests\Api\ApiFormRequest;

class CreateRouteVersionRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAllOf(['route.manage']);
    }

    public function rules(): array
    {
        return [
            'stops' => ['required', 'array', 'min:1'],
            'stops.*.stop_order' => ['required', 'integer', 'min:1'],
            'stops.*.name' => ['nullable', 'string', 'max:255'],
            'stops.*.address' => ['nullable', 'string', 'max:255'],
            'stops.*.lat' => ['nullable', 'numeric'],
            'stops.*.lng' => ['nullable', 'numeric'],
            'stops.*.planned_time' => ['nullable', 'date_format:H:i'],
            'schedules' => ['nullable', 'array'],
            'schedules.*.day_of_week' => ['required_with:schedules', 'integer', 'min:1', 'max:7'],
            'schedules.*.depart_time' => ['required_with:schedules', 'date_format:H:i'],
            'schedules.*.arrive_time' => ['nullable', 'date_format:H:i'],
        ];
    }
}
