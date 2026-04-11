<?php

namespace App\Http\Requests\Api\Operational;

use App\Http\Requests\Api\ApiFormRequest;

class ListDriversRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf(['resource.driver.manage', 'trip.assign']);
    }

    public function rules(): array
    {
        return [
            'employment_status' => ['nullable', 'string', 'in:active,on_leave,terminated'],
            'availability_status' => ['nullable', 'string', 'in:available,busy,offline'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ];
    }
}
