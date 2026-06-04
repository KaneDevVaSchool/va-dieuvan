<?php

namespace App\Http\Requests\Api\P2pPolicy;

use App\Http\Requests\Api\ApiFormRequest;

class UpdateStudentPolicyRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['student_policy.manage']);
    }

    public function rules(): array
    {
        return [
            'student_id' => ['sometimes', 'integer', 'exists:students,id'],
            'route_id' => ['sometimes', 'integer', 'exists:routes,id'],
            'school_year' => ['sometimes', 'string', 'max:9'],
            'semester' => ['sometimes', 'integer', 'in:1,2'],
            'time_slot' => ['sometimes', 'in:morning,afternoon'],
            'pickup_point_id' => ['nullable', 'integer', 'exists:route_stops,id'],
            'dropoff_point_id' => ['nullable', 'integer', 'exists:route_stops,id'],
            'effective_from' => ['sometimes', 'date'],
            'effective_to' => ['sometimes', 'date'],
            'status' => ['sometimes', 'in:active,inactive,suspended'],
        ];
    }
}
