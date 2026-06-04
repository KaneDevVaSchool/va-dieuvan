<?php

namespace App\Http\Requests\Api\P2pPolicy;

use App\Http\Requests\Api\ApiFormRequest;

class StoreStudentPolicyRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['student_policy.manage']);
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'route_id' => ['required', 'integer', 'exists:routes,id'],
            'school_year' => ['required', 'string', 'max:9'],
            'semester' => ['required', 'integer', 'in:1,2'],
            'time_slot' => ['required', 'in:morning,afternoon'],
            'pickup_point_id' => ['nullable', 'integer', 'exists:route_stops,id'],
            'dropoff_point_id' => ['nullable', 'integer', 'exists:route_stops,id'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['required', 'date', 'after_or_equal:effective_from'],
        ];
    }
}
