<?php

namespace App\Http\Requests\Api\D2D;

use App\Http\Requests\Api\ApiFormRequest;

class GenerateRouteTripRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAllOf(['route.manage']);
    }

    public function rules(): array
    {
        return [
            'run_date' => ['required', 'date'],
            'schedule_depart_time' => ['required', 'date_format:H:i'],
            'schedule_arrive_time' => ['nullable', 'date_format:H:i'],
        ];
    }
}
