<?php

namespace App\Http\Requests\Api\Requests;

use App\Http\Requests\Api\ApiFormRequest;

class FillPriceDispatchRequestRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAllOf(['request.fill_price']);
    }

    public function rules(): array
    {
        return [
            'service_price' => ['required', 'numeric', 'min:0'],
        ];
    }
}
