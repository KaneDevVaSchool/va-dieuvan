<?php

namespace App\Http\Requests\Api\Requests;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\DispatchRequest;
use Illuminate\Validation\Rule;

class DecideDispatchRequestRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        $dr = $this->route('dispatchRequest');
        if (! $dr instanceof DispatchRequest) {
            return false;
        }

        if ($dr->trip_type === 'door_to_door') {
            return $this->allowAllOf(['request.approve']);
        }

        return $this->allowAllOf(['request.approve_dept']);
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
