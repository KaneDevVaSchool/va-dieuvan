<?php

namespace App\Http\Requests\Api\Cargo;

use App\Http\Requests\Api\ApiFormRequest;

class UploadCargoPodRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAllOf(['cargo.manage']);
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:10240'], // 10MB
        ];
    }
}
