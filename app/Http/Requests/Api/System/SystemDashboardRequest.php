<?php

namespace App\Http\Requests\Api\System;

use App\Http\Requests\Api\ApiFormRequest;

class SystemDashboardRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['system.roles.manage', 'audit_log.view']);
    }

    public function rules(): array
    {
        return [];
    }
}
