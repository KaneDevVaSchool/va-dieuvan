<?php

namespace App\Http\Requests\Api\TransportProgram;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class BulkDeleteTpProgramsRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['tp_program.manage']);
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => [
                'integer',
                'distinct',
                Rule::exists('tp_programs', 'id')->whereNull('deleted_at'),
            ],
        ];
    }
}
