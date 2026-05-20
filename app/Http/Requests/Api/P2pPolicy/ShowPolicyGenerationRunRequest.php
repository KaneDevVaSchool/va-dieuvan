<?php

namespace App\Http\Requests\Api\P2pPolicy;

use App\Http\Requests\Api\ApiFormRequest;

class ShowPolicyGenerationRunRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['p2p_policy.view', 'p2p_policy.manage', 'p2p_policy.activate']);
    }

    public function rules(): array
    {
        return [];
    }
}
