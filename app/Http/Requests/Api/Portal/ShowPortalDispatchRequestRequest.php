<?php

namespace App\Http\Requests\Api\Portal;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\DispatchRequest;

class ShowPortalDispatchRequestRequest extends ApiFormRequest
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

        return (int) $dr->requester_id === (int) $this->user()->id;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }
}
