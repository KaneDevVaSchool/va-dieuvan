<?php

namespace App\Http\Requests\Api\Portal;

use App\Models\DispatchRequestTemplate;
use App\Models\User;

class UpdatePortalDispatchRequestTemplateRequest extends StorePortalDispatchRequestTemplateRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user instanceof User) {
            return false;
        }

        $template = $this->route('dispatchRequestTemplate');
        if (! $template instanceof DispatchRequestTemplate) {
            return false;
        }

        return (int) $template->requester_id === (int) $user->id;
    }
}
