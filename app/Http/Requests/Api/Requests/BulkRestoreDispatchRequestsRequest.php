<?php

namespace App\Http\Requests\Api\Requests;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class BulkRestoreDispatchRequestsRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return $this->allowAnyOf([
            'trip.view_all',
            'request.approve',
            'request.cancel_own',
            'request.update_own',
            'request.create',
        ]);
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => [
                'integer',
                'distinct',
                Rule::exists('dispatch_requests', 'id')->whereNotNull('deleted_at'),
            ],
        ];
    }
}
