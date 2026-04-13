<?php

namespace App\Http\Requests\Api\Attachments;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class UploadAttachmentRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAllOf(['attachment.upload']);
    }

    public function rules(): array
    {
        return [
            'attachable_type' => ['required', Rule::in(['trip', 'cargo_shipment', 'trip_cost', 'dispatch_request', 'driver_compliance_document'])],
            'attachable_id' => ['required', 'integer', 'min:1'],
            'kind' => ['nullable', 'string', 'max:50'],
            'file' => ['required', 'file', 'max:10240'], // 10MB
        ];
    }
}
