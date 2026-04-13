<?php

namespace App\Http\Requests\Api\Operational;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleComplianceDocumentRequest extends ApiFormRequest
{
    public const DOC_TYPES = [
        'registration',
        'insurance_certificate',
        'inspection_certificate',
        'transport_permit',
        'ownership_proof',
        'lease_contract',
        'maintenance_record',
        'other',
    ];

    public function authorize(): bool
    {
        return $this->allowAnyOf(['resource.vehicle.manage']);
    }

    public function rules(): array
    {
        return [
            'doc_type' => ['required', 'string', Rule::in(self::DOC_TYPES)],
            'title' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'issued_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'file' => ['nullable', 'file', 'max:10240'],
        ];
    }
}
