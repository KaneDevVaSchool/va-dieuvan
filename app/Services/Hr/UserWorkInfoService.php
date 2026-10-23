<?php

namespace App\Services\Hr;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserWorkInfoService
{
    private array $catalogs = [];

    /** HR is the sole source for employee organization and work information. */
    public function forUser(User $user): array
    {
        $fields = ['code', 'phone', 'company_name', 'department_name', 'unit_name', 'headquarter_name', 'working_place', 'position_name', 'concurrent_position_name'];
        $info = array_fill_keys($fields, null);
        $user->loadMissing(['hrEmployee.department', 'hrEmployee.division']);
        $employee = $user->hrEmployee;
        $local = [];
        if ($employee) {
            $labels = $employee->organization_labels ?? [];
            $branch = $this->catalogName('hr_branches', $employee->hr_branch_id);
            $company = $this->catalogName('hr_companies', $employee->hr_company_id);
            $job = $this->catalogName('hr_job_titles', $employee->hr_job_title_id);
            $local = [
                'code' => $employee->employee_code,
                'phone' => $employee->phone,
                'company_name' => $company ?: ($labels['company'] ?? null),
                'department_name' => $employee->department?->name ?: ($labels['department'] ?? null),
                'unit_name' => $employee->division?->name ?: ($labels['division'] ?? null),
                'headquarter_name' => $branch ?: ($labels['branch'] ?? null),
                'position_name' => $job ?: ($labels['job'] ?? null),
            ];
        }
        foreach ($local as $key => $value) {
            if (is_string($value) && trim($value) !== '') {
                $info[$key] = trim($value);
            }
        }
        // Excel has a campus, but no separate working-place column.
        if (trim((string) ($info['working_place'] ?? '')) === '') {
            $info['working_place'] = $info['headquarter_name'] ?? null;
        }

        return $info;
    }

    private function catalogName(string $table, ?int $id): ?string
    {
        if (! $id) {
            return null;
        }
        $this->catalogs[$table] ??= DB::table($table)->pluck('name', 'id')->all();

        return $this->catalogs[$table][$id] ?? null;
    }
}
