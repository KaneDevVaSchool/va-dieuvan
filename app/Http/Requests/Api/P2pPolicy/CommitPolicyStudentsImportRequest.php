<?php

namespace App\Http\Requests\Api\P2pPolicy;

use App\Http\Requests\Api\ApiFormRequest;

class CommitPolicyStudentsImportRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['p2p_policy.import_export']);
    }

    public function rules(): array
    {
        return [
            'preview_id' => ['required', 'uuid'],
        ];
    }
}
