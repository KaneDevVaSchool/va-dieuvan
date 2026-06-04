<?php

namespace App\Http\Requests\Api\P2pPolicy;

use App\Http\Requests\Api\ApiFormRequest;

class MarkPolicyTripStudentAbsentRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf([
            'policy_trip.view',
            'policy_trip.assign_driver',
            'trip.update_status',
        ]);
    }

    public function rules(): array
    {
        return [
            'absence_reason' => ['required', 'in:absent_reported,absent_no_notice,late_cancellation'],
        ];
    }
}
