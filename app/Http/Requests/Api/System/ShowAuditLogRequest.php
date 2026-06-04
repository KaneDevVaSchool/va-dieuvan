<?php

namespace App\Http\Requests\Api\System;

use App\Http\Requests\Api\ApiFormRequest;

class ShowAuditLogRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['audit_log.view']);
    }

    public function rules(): array
    {
        return [];
    }
}
