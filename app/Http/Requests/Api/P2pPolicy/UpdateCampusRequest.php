<?php

namespace App\Http\Requests\Api\P2pPolicy;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateCampusRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['p2p_policy.manage']);
    }

    public function rules(): array
    {
        $campus = $this->route('campus');

        return [
            'code' => ['sometimes', 'string', 'max:32', Rule::unique('campuses', 'code')->ignore($campus?->id)],
            'name' => ['sometimes', 'string', 'max:200'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
