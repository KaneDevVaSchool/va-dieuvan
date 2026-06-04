<?php

namespace App\Http\Requests\Api\P2pPolicy;

use App\Http\Requests\Api\ApiFormRequest;

class PolicyTripLiveUpdatesRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['policy_trip.view']);
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'time_slot' => ['nullable', 'in:morning,afternoon'],
        ];
    }
}
