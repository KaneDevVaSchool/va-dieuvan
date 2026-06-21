<?php

namespace App\Http\Requests\Api\Trips;

use App\Http\Requests\Api\ApiFormRequest;
use App\Support\PassengerStatus;

class TripPassengerSetStatusRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAllOf(['trip.assign']);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:'.implode(',', PassengerStatus::ALL)],
            'at' => ['nullable', 'date'],
            'lock_version' => ['required', 'integer', 'min:0'],
        ];
    }
}
