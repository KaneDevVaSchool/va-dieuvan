<?php

namespace App\Http\Requests\Api\Attachments;

use App\Http\Requests\Api\ApiFormRequest;

class DestroyAttachmentRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAllOf(['attachment.upload']);
    }

    public function rules(): array
    {
        return [];
    }
}
