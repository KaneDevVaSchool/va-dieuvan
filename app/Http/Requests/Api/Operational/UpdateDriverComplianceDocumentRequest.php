<?php

namespace App\Http\Requests\Api\Operational;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateDriverComplianceDocumentRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['resource.driver.manage']);
    }

    public function rules(): array
    {
        return [
            'doc_type' => ['sometimes', 'string', Rule::in(StoreDriverComplianceDocumentRequest::DOC_TYPES)],
            'title' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'issued_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'file' => ['nullable', 'file', 'max:10240'],
            'replace_file' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('replace_file')) {
            $this->merge([
                'replace_file' => filter_var($this->replace_file, FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }
}
