<?php

namespace App\Http\Requests\Api\Requests;

use App\Http\Requests\Api\ApiFormRequest;

class RevertDispatchRequestPaperRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAllOf(['request.paper.manage']);
    }

    public function rules(): array
    {
        return [];
    }
}
