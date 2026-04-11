<?php

namespace App\Http\Requests\Api\Requests;

use App\Http\Requests\Api\ApiFormRequest;

class MarkDispatchRequestPaperReceivedRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAllOf(['request.paper.manage']);
    }

    public function rules(): array
    {
        return [
            'paper_received_at' => ['nullable', 'date'],
            'paper_reference' => ['nullable', 'string', 'max:255'],
        ];
    }
}
