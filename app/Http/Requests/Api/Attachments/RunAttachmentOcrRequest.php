<?php

namespace App\Http\Requests\Api\Attachments;

use App\Http\Requests\Api\ApiFormRequest;

class RunAttachmentOcrRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['attachment.upload', 'request.paper.manage']);
    }

    public function rules(): array
    {
        return [];
    }
}
