<?php

namespace App\Http\Requests\Api\Driver;

use App\Http\Requests\Api\ApiFormRequest;

class CompletePolicyTripRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['trip.update_status', 'policy_trip.assign_driver']);
    }

    public function rules(): array
    {
        return [
            // Xác nhận hoàn thành dù còn HS đã lên nhưng chưa tích xuống (§5.2).
            'confirm' => ['nullable', 'boolean'],
        ];
    }
}
