<?php

namespace App\Http\Requests\Api\Portal;

use App\Http\Requests\Api\Requests\StoreDispatchRequestTemplateRequest;
use App\Models\User;

/**
 * Portal — người đề xuất không có dispatch.web / permission request.create.
 */
class StorePortalDispatchRequestTemplateRequest extends StoreDispatchRequestTemplateRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User
            && ! $user->canAccessDispatchWebApp()
            && ! $user->canAccessDriverWebApp();
    }
}
