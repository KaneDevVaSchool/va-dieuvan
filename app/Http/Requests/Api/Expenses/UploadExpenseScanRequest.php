<?php

namespace App\Http\Requests\Api\Expenses;

use App\Http\Requests\Api\ApiFormRequest;
use App\Services\Attachments\AttachmentStoreService;

class UploadExpenseScanRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['expense.submit', 'expense.manage']);
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', AttachmentStoreService::FILE_MIMES_RULE, 'max:'.AttachmentStoreService::MAX_KB],
        ];
    }
}
