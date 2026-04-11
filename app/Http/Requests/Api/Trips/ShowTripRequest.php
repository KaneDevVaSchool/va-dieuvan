<?php

namespace App\Http\Requests\Api\Trips;

use App\Http\Requests\Api\ApiFormRequest;

class ShowTripRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['trip.view_all', 'trip.view_own']);
    }

    public function rules(): array
    {
        return [];
    }
}
