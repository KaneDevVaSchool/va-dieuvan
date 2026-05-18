<?php

namespace App\Http\Requests\Api\Portal;

use App\Http\Requests\Api\ApiFormRequest;

class SummaryPortalDispatchRequestsRequest extends ApiFormRequest
{
    use EnsuresPortalUser;

    public function authorize(): bool
    {
        return $this->portalUserMayAccess($this->user());
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }
}
