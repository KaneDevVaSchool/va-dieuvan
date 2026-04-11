<?php

namespace App\Http\Requests\Api\Requests;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class DecideDispatchRequestRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAllOf(['request.approve']);
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
