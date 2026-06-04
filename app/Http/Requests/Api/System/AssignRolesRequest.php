<?php

namespace App\Http\Requests\Api\System;

use App\Http\Requests\Api\ApiFormRequest;

class AssignRolesRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['system.user_roles.manage']);
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'is_temporary' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
