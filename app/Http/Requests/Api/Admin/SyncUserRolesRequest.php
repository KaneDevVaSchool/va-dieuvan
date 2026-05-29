<?php

namespace App\Http\Requests\Api\Admin;

use App\Http\Requests\Api\ApiFormRequest;

class SyncUserRolesRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['system.user_roles.manage']);
    }

    public function rules(): array
    {
        return [
            'role_ids' => ['required', 'array', 'size:1'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'role_ids.size' => 'Mỗi người dùng chỉ được gán đúng 1 vai trò.',
        ];
    }
}
