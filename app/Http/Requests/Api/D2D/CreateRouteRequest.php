<?php

namespace App\Http\Requests\Api\D2D;

use App\Http\Requests\Api\ApiFormRequest;

class CreateRouteRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAllOf(['route.manage']);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
