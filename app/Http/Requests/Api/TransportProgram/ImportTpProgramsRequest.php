<?php

namespace App\Http\Requests\Api\TransportProgram;

use App\Http\Requests\Api\ApiFormRequest;

class ImportTpProgramsRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['tp_program.manage']);
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ];
    }
}
