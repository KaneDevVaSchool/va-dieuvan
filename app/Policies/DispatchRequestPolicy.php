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
            || $user->hasPermission('request.approve_dept')
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

        if ($user->hasPermission('request.approve_dept')
            && ! $user->hasPermission('trip.view_all')
            && ! $user->hasPermission('request.approve')) {
            $dispatchRequest->loadMissing('requester:id,department_id');

            return $this->approveDeptLimitedViewEligible($user, $dispatchRequest);
        }

        return (int) $dispatchRequest->requester_id === (int) $user->id;
    }

    public function adjustPassengerCount(User $user, DispatchRequest $dispatchRequest): bool
    {
        if ($dispatchRequest->trashed()) {
            return false;
        }

        if ($dispatchRequest->dispatch_request_template_id === null) {
            return false;
        }

        if ($user->hasPermission('trip.view_all')) {
            return true;
        }

        return (int) $dispatchRequest->requester_id === (int) $user->id
            && $user->hasPermission('request.update_own');
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

        if ($user->hasPermission('trip.view_all')
            || $user->hasPermission('request.approve')
            || $user->hasPermission('request.approve_dept')) {
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
        if ($user->hasPermission('trip.view_all')
            || $user->hasPermission('request.approve')) {
            return true;
        }

        if ($user->hasPermission('request.approve_dept')
            && ! $user->hasPermission('trip.view_all')
            && ! $user->hasPermission('request.approve')) {
            $dispatchRequest->loadMissing('requester:id,department_id');

            return $this->approveDeptLimitedViewEligible($user, $dispatchRequest);
        }

        return (int) $dispatchRequest->requester_id === (int) $user->id
            && ($user->hasPermission('request.cancel_own')
                || $user->hasPermission('request.update_own')
                || $user->hasPermission('request.create'));
    }

    /** Trưởng BP «thuần»: cùng phòng với người đề xuất và (nếu phiếu đã gán) chỉ được xem phiếu gán cho mình — trừ bản legacy `assigned_dept_head_id` null. */
    private function approveDeptLimitedViewEligible(User $user, DispatchRequest $dispatchRequest): bool
    {
        $requester = $dispatchRequest->requester;
        if ($requester === null
            || $user->department_id === null
            || (int) $requester->department_id !== (int) $user->department_id) {
            return false;
        }

        if ($dispatchRequest->assigned_dept_head_id !== null
            && (int) $dispatchRequest->assigned_dept_head_id !== (int) $user->id) {
            return false;
        }

        return true;
    }
}
