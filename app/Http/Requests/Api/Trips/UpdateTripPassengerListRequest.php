<?php

namespace App\Http\Requests\Api\Trips;

use App\Http\Requests\Api\ApiFormRequest;

class UpdateTripPassengerListRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAllOf(['trip.assign']);
    }

    public function rules(): array
    {
        return [
            'passenger_rows' => ['sometimes', 'array', 'max:50'],
            'business_rows' => ['sometimes', 'array', 'max:50'],
            'cargo_rows' => ['sometimes', 'array', 'max:50'],
        ];
    }
}
