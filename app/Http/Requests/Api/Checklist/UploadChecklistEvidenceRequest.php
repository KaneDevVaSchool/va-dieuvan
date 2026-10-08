<?php

namespace App\Http\Requests\Api\Checklist;

use App\Services\Attachments\AttachmentStoreService;

class UploadChecklistEvidenceRequest extends DriverChecklistRequest
{
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', AttachmentStoreService::FILE_MIMES_RULE, 'max:'.AttachmentStoreService::MAX_KB],
        ];
    }
}
