<?php

namespace App\Http\Requests\Api\P2pPolicy;

use App\Http\Requests\Api\ApiFormRequest;

class DestroyP2pPolicyTermRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['p2p_policy.manage']);
    }

    public function rules(): array
    {
        return [];
    }
}
