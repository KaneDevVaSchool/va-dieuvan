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

    public function approveDept(User $user, DispatchRequest $dispatchRequest): bool
    {
        if ($dispatchRequest->trashed()) {
            return false;
        }

        if ($dispatchRequest->trip_type === 'door_to_door') {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->hasPermission('request.approve_dept')) {
            return false;
        }

        if ($user->hasPermission('trip.view_all') || $user->hasPermission('request.approve')) {
            return true;
        }

        return $this->approveDeptLimitedViewEligible($user, $dispatchRequest);
    }

    /** Trưởng BP «thuần»: nếu phiếu có `assigned_dept_head_id` thì chỉ người đó; phiếu chưa gán (legacy) vẫn theo người đề xuất cùng phòng — trừ khi user không có phòng trong hồ sơ thì chỉ xem phiếu được gán cho mình. */
    private function approveDeptLimitedViewEligible(User $user, DispatchRequest $dispatchRequest): bool
    {
        if ($dispatchRequest->assigned_dept_head_id !== null) {
            return (int) $dispatchRequest->assigned_dept_head_id === (int) $user->id;
        }

        if ($user->department_id === null) {
            return false;
        }

        $dispatchRequest->loadMissing('requester:id,department_id');
        $requester = $dispatchRequest->requester;

        return $requester !== null && (int) $requester->department_id === (int) $user->department_id;
    }
}
