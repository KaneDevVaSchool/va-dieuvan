<?php

namespace App\Http\Requests\Api\D2D;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class RouteVersionDecisionRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAllOf(['route.manage']);
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['approve', 'archive'])],
        ];
    }
}
