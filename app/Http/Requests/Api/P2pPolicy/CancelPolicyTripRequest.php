<?php

namespace App\Http\Requests\Api\P2pPolicy;

use App\Http\Requests\Api\ApiFormRequest;

class CancelPolicyTripRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['policy_trip.cancel']);
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }
}
