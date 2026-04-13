<?php

namespace App\Http\Requests\Api\Operational;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class StoreDriverComplianceDocumentRequest extends ApiFormRequest
{
    public const DOC_TYPES = [
        'id_card',
        'license',
        'medical_certificate',
        'criminal_record',
        'training_certificate',
        'labor_contract',
        'social_insurance',
        'other',
    ];

    public function authorize(): bool
    {
        return $this->allowAnyOf(['resource.driver.manage']);
    }

    public function rules(): array
    {
        return [
            'doc_type' => ['required', 'string', Rule::in(self::DOC_TYPES)],
            'title' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'issued_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:issued_at'],
            'file' => ['nullable', 'file', 'max:10240'],
        ];
    }
}
