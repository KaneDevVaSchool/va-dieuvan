<?php

namespace App\Http\Requests\Api\Requests;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\DispatchRequest;

class ExportDispatchRequestPdfRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        $dr = $this->route('dispatchRequest');
        if (! $dr instanceof DispatchRequest) {
            return false;
        }

        return $this->user() && $this->user()->can('view', $dr);
    }

    public function rules(): array
    {
        return [];
    }
}
