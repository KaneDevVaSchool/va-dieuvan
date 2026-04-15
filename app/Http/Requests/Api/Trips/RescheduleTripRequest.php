<?php

namespace App\Http\Requests\Api\Trips;

use App\Http\Requests\Api\ApiFormRequest;

class RescheduleTripRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAllOf(['trip.assign']);
    }

    public function rules(): array
    {
        return [
            'depart_at' => ['required', 'date'],
            'lock_version' => ['required', 'integer', 'min:0'],
        ];
    }
}
