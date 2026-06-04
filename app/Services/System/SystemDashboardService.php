<?php

namespace App\Services\System;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\User;
use Illuminate\Support\Carbon;

class SystemDashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        $today = Carbon::today();

        return [
            'users_total' => User::query()->count(),
            'roles_total' => Role::query()->count(),
            'roles_active' => Role::query()->where('status', 'active')->orWhereNull('status')->count(),
            'assignments_active' => RoleAssignment::query()->where('status', 'active')->count(),
            'audit_today' => AuditLog::query()->whereDate('created_at', $today)->count(),
            'audit_week' => AuditLog::query()->where('created_at', '>=', $today->copy()->subDays(7))->count(),
        ];
    }
}
