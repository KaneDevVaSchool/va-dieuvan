<?php

namespace App\Http\Requests\Api\Payments;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class ExecutePaymentRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAllOf(['payment.execute']);
    }

    public function rules(): array
    {
        return [
            'method' => ['required', 'string', 'max:50'],
            'reference' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['paid', 'failed', 'cancelled'])],
        ];
    }
}
