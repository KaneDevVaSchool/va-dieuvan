<?php

namespace App\Http\Requests\Api\Requests;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\DispatchRequest;
use App\Models\User;
use Illuminate\Validation\Rule;

class DeptDecideDispatchRequestRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user instanceof User) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->hasPermission('request.approve_dept')) {
            return false;
        }

        /** @var mixed $dr */
        $dr = $this->route('dispatchRequest');
        if (! $dr instanceof DispatchRequest) {
            return false;
        }

        if ($dr->trip_type === 'door_to_door') {
            return false;
        }

        $requester = $dr->requester;

        return $requester !== null
            && $user->department_id !== null
            && (int) $requester->department_id === (int) $user->department_id;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'rejection_reason' => [
                Rule::requiredIf(fn () => $this->input('decision') === 'reject'),
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }
}
