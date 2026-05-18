<?php

namespace App\Http\Requests\Api\Portal;

use App\Models\User;

trait EnsuresPortalUser
{
    protected function portalUserMayAccess(?User $user): bool
    {
        return $user instanceof User
            && ! $user->canAccessDispatchWebApp()
            && ! $user->canAccessDriverWebApp();
    }
}
