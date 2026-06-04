<?php

namespace App\Http\Requests\Api\TransportProgram;

use App\Http\Requests\Api\ApiFormRequest;

class ListTpProgramsRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['tp_program.view', 'tp_program.manage']);
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'in:draft,active,paused,completed,cancelled'],
            'responsible_user_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
