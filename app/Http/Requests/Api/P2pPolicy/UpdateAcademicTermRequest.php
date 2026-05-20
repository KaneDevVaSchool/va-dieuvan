<?php

namespace App\Http\Requests\Api\P2pPolicy;

use App\Http\Requests\Api\ApiFormRequest;

class UpdateAcademicTermRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['p2p_policy.manage']);
    }

    public function rules(): array
    {
        return [
            'academic_year' => ['sometimes', 'string', 'max:20'],
            'term_code' => ['sometimes', 'string', 'max:10'],
            'name' => ['sometimes', 'string', 'max:120'],
            'starts_on' => ['sometimes', 'date'],
            'ends_on' => ['sometimes', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
