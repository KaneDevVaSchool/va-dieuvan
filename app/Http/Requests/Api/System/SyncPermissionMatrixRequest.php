<?php

namespace App\Http\Requests\Api\System;

use App\Http\Requests\Api\ApiFormRequest;

class SyncPermissionMatrixRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['system.permissions.manage']);
    }

    public function rules(): array
    {
        return [
            'changes' => ['required', 'array'],
            'changes.*.role_id' => ['required', 'integer', 'exists:roles,id'],
            'changes.*.grant' => ['nullable', 'array'],
            'changes.*.grant.*' => ['integer', 'exists:permissions,id'],
            'changes.*.revoke' => ['nullable', 'array'],
            'changes.*.revoke.*' => ['integer', 'exists:permissions,id'],
        ];
    }
}
