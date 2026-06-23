<?php

namespace App\Http\Requests\Api\Operational;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\User;

class SearchDeptHeadsForDispatchFormRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->canAccessDispatchWebApp();
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'pick' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
