<?php

namespace App\Http\Requests\Api\Operational;

use App\Http\Requests\Api\ApiFormRequest;

class StoreTransportProviderRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['resource.provider.manage']);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:vendor,taxi'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:64'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
            'contract_number' => ['nullable', 'string', 'max:128'],
            'contract_signed_at' => ['nullable', 'date'],
            'contract_expires_at' => ['nullable', 'date'],
            'services' => ['nullable', 'array', 'max:50'],
            'services.*.kind' => ['required', 'string', 'in:solution,service'],
            'services.*.name' => ['required', 'string', 'max:255'],
            'services.*.note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
