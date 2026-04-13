<?php

namespace App\Http\Requests\Api\Admin;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class ListUsersForRolesRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['system.user_roles.manage']);
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'assignment' => ['nullable', Rule::in(['all', 'assigned', 'unassigned'])],
            'per_page' => ['nullable', Rule::in(['5', '10', '15', '20', '25', 'all'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('assignment') && $this->input('assignment') === '') {
            $this->merge(['assignment' => 'all']);
        }
    }
}
