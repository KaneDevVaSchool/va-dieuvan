<?php

namespace App\Http\Requests\Api\Portal;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\DispatchRequest;
use Illuminate\Validation\Rule;

class PortalPatchSigningWorkflowRequest extends ApiFormRequest
{
    use EnsuresPortalUser;

    public function authorize(): bool
    {
        if (! $this->portalUserMayAccess($this->user())) {
            return false;
        }

        $dr = $this->route('dispatchRequest');
        if (! $dr instanceof DispatchRequest || $dr->trashed()) {
            return false;
        }

        if ((int) $dr->requester_id !== (int) $this->user()->id) {
            return false;
        }

        return $dr->status === 'approved';
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::in([
                    'awaiting_signature',
                    'signing_in_progress',
                    'signing_complete',
                    'signed_uploaded',
                ]),
            ],
        ];
    }
}
