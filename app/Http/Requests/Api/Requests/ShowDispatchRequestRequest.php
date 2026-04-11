<?php

namespace App\Http\Requests\Api\Requests;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\DispatchRequest;

class ShowDispatchRequestRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        /** @var DispatchRequest $dispatchRequest */
        $dispatchRequest = $this->route('dispatchRequest');

        return $this->user()->can('view', $dispatchRequest);
    }

    public function rules(): array
    {
        return [];
    }
}
