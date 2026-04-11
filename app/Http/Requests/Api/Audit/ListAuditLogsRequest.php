<?php

namespace App\Http\Requests\Api\Audit;

use App\Http\Requests\Api\ApiFormRequest;

class ListAuditLogsRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAllOf(['audit_log.view']);
    }

    public function rules(): array
    {
        return [
            'actor_id' => ['nullable', 'integer', 'min:1'],
            'event' => ['nullable', 'string', 'max:255'],
            'auditable_type' => ['nullable', 'string', 'max:255'],
            'auditable_id' => ['nullable', 'integer', 'min:1'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
