<?php

namespace App\Http\Requests\Api\P2pPolicy;

use App\Http\Requests\Api\ApiFormRequest;

class StoreAcademicTermRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['p2p_policy.manage']);
    }

    public function rules(): array
    {
        return [
            'academic_year' => ['required', 'string', 'max:20'],
            'term_code' => ['required', 'string', 'max:10'],
            'name' => ['required', 'string', 'max:120'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
