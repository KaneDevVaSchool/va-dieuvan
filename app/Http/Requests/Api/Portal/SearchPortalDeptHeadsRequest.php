<?php

namespace App\Http\Requests\Api\Portal;

use App\Http\Requests\Api\ApiFormRequest;

class SearchPortalDeptHeadsRequest extends ApiFormRequest
{
    use EnsuresPortalUser;

    public function authorize(): bool
    {
        return $this->portalUserMayAccess($this->user());
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'pick' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
