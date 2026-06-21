<?php

namespace App\Http\Requests\Api\Trips;

use App\Http\Requests\Api\ApiFormRequest;
use App\Support\PassengerStatus;

class TripPassengerBulkStatusRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAllOf(['trip.assign']);
    }

    public function rules(): array
    {
        return [
            'keys' => ['required', 'array', 'min:1', 'max:300'],
            'keys.*' => ['required', 'string', 'max:64', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'status' => ['required', 'string', 'in:'.implode(',', PassengerStatus::ALL)],
            'at' => ['nullable', 'date'],
            'lock_version' => ['required', 'integer', 'min:0'],
        ];
    }
}
