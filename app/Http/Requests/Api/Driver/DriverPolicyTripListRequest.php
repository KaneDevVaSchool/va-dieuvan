<?php

namespace App\Http\Requests\Api\Driver;

use App\Http\Requests\Api\ApiFormRequest;

class DriverPolicyTripListRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['trip.view_own', 'trip.update_status', 'policy_trip.view']);
    }

    public function rules(): array
    {
        return [
            // 'today' hoặc YYYY-MM-DD; mặc định hôm nay khi rỗng.
            'date' => ['nullable', 'string', 'max:10'],
        ];
    }
}
