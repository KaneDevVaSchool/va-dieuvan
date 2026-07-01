<?php

namespace App\Http\Requests\Api\Resources;

use App\Http\Requests\Api\ApiFormRequest;

class ImportDriversRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ];
    }
}
