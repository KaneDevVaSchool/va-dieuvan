<?php

namespace App\Http\Requests\Api\Portal;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\DispatchRequest;
use App\Models\User;

class PortalUploadSignedPaperRequest extends ApiFormRequest
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
            'file' => ['required', 'file', 'max:10240'],
        ];
    }
}
