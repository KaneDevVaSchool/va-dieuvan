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
        if ($dispatchRequest->trashed()) {
            return $this->canManageTrashed($user, $dispatchRequest);
        }

        if ($user->hasPermission('trip.view_all')
            || $user->hasPermission('request.approve')
            || $user->hasPermission('request.paper.manage')) {
            return true;
        }

        return (int) $dispatchRequest->requester_id === (int) $user->id;
    }

    /**
     * Soft-delete (move to trash).
     */
    public function delete(User $user, DispatchRequest $dispatchRequest): bool
    {
        if ($dispatchRequest->trashed()) {
            return false;
        }

        if (! $this->view($user, $dispatchRequest)) {
            return false;
        }

        if ($user->hasPermission('trip.view_all') || $user->hasPermission('request.approve')) {
            return true;
        }

        if ((int) $dispatchRequest->requester_id !== (int) $user->id) {
            return false;
        }

        if ($user->hasPermission('request.cancel_own') || $user->hasPermission('request.update_own')) {
            return in_array($dispatchRequest->status, ['draft', 'cancelled'], true);
        }

        if ($user->hasPermission('request.create')) {
            return $dispatchRequest->status === 'draft';
        }

        return false;
    }

    public function restore(User $user, DispatchRequest $dispatchRequest): bool
    {
        if (! $dispatchRequest->trashed()) {
            return false;
        }

        return $this->canManageTrashed($user, $dispatchRequest);
    }

    /**
     * Permanently remove a soft-deleted request (cannot be undone).
     */
    public function forceDelete(User $user, DispatchRequest $dispatchRequest): bool
    {
        if (! $dispatchRequest->trashed()) {
            return false;
        }

        return $this->canManageTrashed($user, $dispatchRequest);
    }

    private function canManageTrashed(User $user, DispatchRequest $dispatchRequest): bool
    {
        if ($user->hasPermission('trip.view_all') || $user->hasPermission('request.approve')) {
            return true;
        }

        return (int) $dispatchRequest->requester_id === (int) $user->id
            && ($user->hasPermission('request.cancel_own')
                || $user->hasPermission('request.update_own')
                || $user->hasPermission('request.create'));
    }
}
