<?php

namespace App\Services\Hr;

use App\Models\User;

class EmployeeManagerResolver
{
    /** Resolve an eligible approver; never assign a requester to approve their own request. */
    public function resolve(User $requester): ?User
    {
        $employee = $requester->hrEmployee()->with(['division.manager', 'department.manager'])->first();
        $department = $employee?->department;
        $candidates = [$employee?->division?->manager, $department?->manager];
        foreach ($candidates as $candidate) {
            if ($candidate && $candidate->id !== $requester->id && $candidate->is_active && $candidate->hasRole('department_head')) {
                return $candidate;
            }
        }

        return null;
    }
}
