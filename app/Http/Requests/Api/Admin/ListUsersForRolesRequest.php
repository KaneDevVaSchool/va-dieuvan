<?php

namespace App\Http\Requests\Api\Admin;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\Role;
use Illuminate\Validation\Rule;

class ListUsersForRolesRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['system.user_roles.manage']);
    }

    public function rules(): array
    {
        $guard = 'web';
        $allowedRoleNames = Role::query()->where('guard_name', $guard)->pluck('name')->all();

        return [
            'q' => ['nullable', 'string', 'max:255'],
            'assignment' => ['nullable', Rule::in(['all', 'assigned', 'unassigned'])],
            'per_page' => ['nullable', Rule::in(['5', '10', '15', '20', '25', '50', '100', 'all'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'roles' => ['nullable', 'array', 'max:50'],
            'roles.*' => ['string', 'max:255', Rule::in($allowedRoleNames)],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('assignment') && $this->input('assignment') === '') {
            $this->merge(['assignment' => 'all']);
        }
    }
}
