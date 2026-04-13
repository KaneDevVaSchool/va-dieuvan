<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class CmsUserInfoService
{
    /**
     * Cột ghi được trên CMS `user_info` (theo scripts/user_info.sql).
     * Không gồm id, user_id (user_id chỉ khi insert), created_at/updated_at (tự quản).
     */
    private const EDITABLE_DB_COLUMNS = [
        'code',
        'gender',
        'birthdate',
        'birth_place',
        'national',
        'religion',
        'hometown',
        'identity',
        'identity_date',
        'identity_place',
        'tax_code',
        'social_insurance_number',
        'phone',
        'address',
        'household',
        'bank_account',
        'bank',
        'start_working_date',
        'working_place',
        'note',
        'company_name',
        'department_name',
        'unit_name',
        'headquarter_name',
        'position_name',
        'concurrent_position_name',
        'department_id',
        'company_id',
        'health_insurance_code',
        'unemployment_insurance_number',
    ];

    private const DATE_COLUMNS = ['birthdate', 'identity_date', 'start_working_date'];

    private const INT_COLUMNS = ['gender', 'department_id', 'company_id'];

    public function isAvailable(): bool
    {
        try {
            DB::connection('cms')->getPdo();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public function findCmsUserIdByEmail(string $email): ?int
    {
        if (! $this->isAvailable()) {
            return null;
        }

        $row = DB::connection('cms')
            ->table('users')
            ->where('email', $email)
            ->first();

        return $row ? (int) $row->id : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getLatestUserInfoRow(int $cmsUserId): ?array
    {
        if (! $this->isAvailable()) {
            return null;
        }

        $row = DB::connection('cms')
            ->table('user_info')
            ->where('user_id', $cmsUserId)
            ->orderByDesc('id')
            ->first();

        if (! $row) {
            return null;
        }

        return json_decode(json_encode($row), true);
    }

    /**
     * Payload từ API: khóa trùng tên cột DB + employee_code → code.
     * Chỉ ghi các khóa có trong request (sau validated).
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    public function updateUserInfo(int $cmsUserId, array $payload): ?array
    {
        if (! $this->isAvailable()) {
            return null;
        }

        $update = [];

        if (array_key_exists('employee_code', $payload)) {
            $update['code'] = $this->normalizeString($payload['employee_code']);
        }

        foreach (self::EDITABLE_DB_COLUMNS as $col) {
            if ($col === 'code') {
                continue;
            }
            if (! array_key_exists($col, $payload)) {
                continue;
            }
            if (! $this->cmsHasColumn('user_info', $col)) {
                continue;
            }
            $update[$col] = $this->normalizeColumnValue($col, $payload[$col]);
        }

        if ($update === []) {
            return $this->getLatestUserInfoRow($cmsUserId);
        }

        $cms = DB::connection('cms');
        $latest = $cms->table('user_info')
            ->where('user_id', $cmsUserId)
            ->orderByDesc('id')
            ->first();

        $hasUpdatedAt = $this->cmsHasColumn('user_info', 'updated_at');

        if ($latest) {
            if ($hasUpdatedAt) {
                $update['updated_at'] = now();
            }
            $cms->table('user_info')->where('id', $latest->id)->update($update);
        } else {
            $insert = array_merge(['user_id' => $cmsUserId], $update);
            if ($this->cmsHasColumn('user_info', 'created_at')) {
                $insert['created_at'] = now();
            }
            if ($hasUpdatedAt) {
                $insert['updated_at'] = now();
            }
            $cms->table('user_info')->insert($insert);
        }

        return $this->getLatestUserInfoRow($cmsUserId);
    }

    protected function normalizeString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_string($value) ? $value : (string) $value;
    }

    protected function normalizeColumnValue(string $column, mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (in_array($column, self::INT_COLUMNS, true)) {
            if ($column === 'gender') {
                return ((int) $value) === 1 ? 1 : 0;
            }

            $n = (int) $value;

            return $n > 0 ? $n : null;
        }

        if (in_array($column, self::DATE_COLUMNS, true)) {
            try {
                return Carbon::parse($value)->format('Y-m-d H:i:s');
            } catch (Throwable) {
                return null;
            }
        }

        return is_string($value) ? $value : (string) $value;
    }

    protected function cmsHasColumn(string $table, string $column): bool
    {
        try {
            return Schema::connection('cms')->hasColumn($table, $column);
        } catch (Throwable) {
            return false;
        }
    }
}
