<?php

namespace App\Http\Requests\Api\Operational;

use App\Http\Requests\Api\ApiFormRequest;

class DeleteTransportProviderRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['resource.provider.manage']);
    }

    public function rules(): array
    {
        return [];
    }
}
