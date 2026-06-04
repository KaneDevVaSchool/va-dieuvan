<?php

namespace App\Http\Requests\Api\P2pPolicy;

use App\Http\Requests\Api\ApiFormRequest;

class GeneratePolicyTripsRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['policy_trip.assign_driver', 'policy_trip.view']);
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
        ];
    }
}
