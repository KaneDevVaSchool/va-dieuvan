<?php

namespace App\Http\Requests\Api\P2pPolicy;

use App\Http\Requests\Api\ApiFormRequest;

class StoreCampusRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['p2p_policy.manage']);
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:32', 'unique:campuses,code'],
            'name' => ['required', 'string', 'max:200'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
