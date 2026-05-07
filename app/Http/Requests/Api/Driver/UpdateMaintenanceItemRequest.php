<?php

namespace App\Http\Requests\Api\Driver;

use App\Http\Requests\Api\ApiFormRequest;

class UpdateMaintenanceItemRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        $u = $this->user();

        return $u && $u->canAccessDriverWebApp();
    }

    public function rules(): array
    {
        return [
            'expiry_date'           => ['sometimes', 'nullable', 'date'],
            'next_service_date'     => ['sometimes', 'nullable', 'date'],
            'next_service_km'       => ['sometimes', 'nullable', 'integer', 'min:0'],
            'notes'                 => ['sometimes', 'nullable', 'string', 'max:2000'],
            'reminder_enabled'      => ['sometimes', 'boolean'],
            'reminder_days_before'  => ['sometimes', 'nullable', 'integer', 'in:7,14,30,60'],
            'issued_by'             => ['sometimes', 'nullable', 'string', 'max:255'],
            'estimated_renewal_cost' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'amount_paid'           => ['sometimes', 'nullable', 'integer', 'min:0'],
            'renewal_notes'         => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
