<?php

namespace App\Http\Requests\Api\Driver;

use App\Http\Requests\Api\ApiFormRequest;

/** Hành động luồng tài xế không cần body: start, tích lên/xuống xe, xem HS. */
class DriverPolicyTripActionRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['trip.update_status', 'policy_trip.assign_driver', 'policy_trip.view']);
    }

    public function rules(): array
    {
        return [];
    }
}
