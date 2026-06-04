<?php

namespace App\Http\Requests\Api\P2pPolicy;

use App\Http\Requests\Api\ApiFormRequest;

class SearchPolicyStudentsRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['student_policy.manage']);
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:120'],
        ];
    }
}
