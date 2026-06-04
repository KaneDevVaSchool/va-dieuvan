<?php

namespace App\Http\Requests\Api\P2pPolicy;

use App\Http\Requests\Api\ApiFormRequest;

class ListPolicyRoutesRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['policy_trip.view', 'student_policy.manage']);
    }

    public function rules(): array
    {
        return [];
    }
}
