<?php

namespace App\Http\Requests\Api\Admin;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\Role;
use Illuminate\Validation\Rule;

class BulkUpdateUserRolesRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['system.user_roles.manage']);
    }

    public function rules(): array
    {
        $allowedRoleNames = Role::query()->where('guard_name', 'web')->pluck('name')->all();

        return [
            'user_ids' => ['required', 'array', 'min:1', 'max:500'],
            'user_ids.*' => ['integer', 'distinct', 'exists:users,id'],
            'roles' => ['required', 'array', 'min:1', 'max:50'],
            'roles.*' => ['string', 'max:255', Rule::in($allowedRoleNames)],
            'action' => ['required', Rule::in(['assign', 'remove'])],
        ];
    }
}
