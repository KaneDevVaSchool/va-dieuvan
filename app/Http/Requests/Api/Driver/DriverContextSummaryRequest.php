<?php

namespace App\Http\Requests\Api\Driver;

use App\Http\Requests\Api\ApiFormRequest;

class DriverContextSummaryRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        $u = $this->user();

        return $u && $u->canAccessDriverWebApp();
    }

    public function rules(): array
    {
        return [];
    }
}
