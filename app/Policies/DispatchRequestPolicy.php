<?php

namespace App\Policies;

use App\Models\DispatchRequest;
use App\Models\User;

class DispatchRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('trip.view_all')
            || $user->hasPermission('request.create')
            || $user->hasPermission('request.update_own')
            || $user->hasPermission('request.cancel_own')
            || $user->hasPermission('request.approve')
            || $user->hasPermission('request.paper.manage');
    }

    public function view(User $user, DispatchRequest $dispatchRequest): bool
    {
        if ($user->hasPermission('trip.view_all')
            || $user->hasPermission('request.approve')
            || $user->hasPermission('request.paper.manage')) {
            return true;
        }

        return (int) $dispatchRequest->requester_id === (int) $user->id;
    }
}
