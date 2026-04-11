<?php

namespace App\Http\Requests\Api\Trips;

use App\Http\Requests\Api\ApiFormRequest;

class ListTripsRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['trip.view_all', 'trip.view_own']);
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', 'max:50'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
