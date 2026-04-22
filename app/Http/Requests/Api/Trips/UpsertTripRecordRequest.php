<?php

namespace App\Http\Requests\Api\Trips;

use App\Http\Requests\Api\ApiFormRequest;

class UpsertTripRecordRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAllOf(['trip.record.create']);
    }

    public function rules(): array
    {
        return [
            'start_odometer_km' => ['nullable', 'integer', 'min:0'],
            'end_odometer_km' => ['nullable', 'integer', 'min:0'],
            'driver_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
