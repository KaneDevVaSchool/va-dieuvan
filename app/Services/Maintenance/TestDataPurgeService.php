<?php

namespace App\Services\Maintenance;

use App\Models\Role;
use App\Models\User;
use App\Services\Auditing\AuditLogger;
use App\Support\Roles;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;

/**
 * Xóa dữ liệu kiểm thử trước khi go-live.
 *
 * Thứ tự bảng trong mỗi nhóm là con → cha để FK (restrict/cascade) không chặn giữa chừng.
 * Toàn bộ DELETE chạy trong một transaction; file vật lý chỉ bị xóa sau khi commit.
 */
class TestDataPurgeService
{
    public const GROUP_BUSINESS = 'business';

    public const GROUP_FLEET = 'fleet';

    public const GROUP_TP_STUDENTS = 'tp_students';

    public const GROUP_AUDIT = 'audit';

    public const GROUP_PRICING = 'pricing';

    /** @var array<string, list<string>> */
    public const GROUPS = [
        self::GROUP_BUSINESS => [
            'signed_document_verifications',
            'signed_document_versions',
            'attachments',
            'payments',
            'reconciliation_periods',
            'trip_events',
            'trip_records',
            'trip_costs',
            'trip_passengers',
            'cargo_shipments',
            'tp_trip_student_logs',
            'tp_day_absences',
            'tp_trip_executions',
            'tp_enrollments',
            'tp_import_rows',
            'tp_import_batches',
            'tp_program_days',
            'tp_programs',
            'trips',
            'dispatch_requests',
            'dispatch_request_templates',
            'dispatch_packages',
            'portal_form_templates',
            'dispatch_import_batches',
            'idempotent_requests',
        ],
        self::GROUP_FLEET => [
            'driver_compliance_documents',
            'vehicle_compliance_documents',
            'vehicles',
            'drivers',
        ],
        self::GROUP_TP_STUDENTS => [
            'tp_students',
        ],
        self::GROUP_AUDIT => [
            'tp_audit_logs',
            'audit_logs',
            'notifications',
        ],
        self::GROUP_PRICING => [
            'reference_pricing_revisions',
            'pricing_notes',
            'passenger_fare_rates',
            'cargo_fare_rates',
        ],
    ];

    /** Bảng cấu hình / hệ thống luôn giữ nguyên. */
    public const KEEP_TABLES = [
        'migrations',
        'users',
        'password_reset_tokens',
        'personal_access_tokens',
        'failed_jobs',
        'jobs',
        'sessions',
        'cache',
        'cache_locks',
        'departments',
        'feature_toggles',
        'dispatch_settings',
        'menu_items',
        'role_assignments',
        'user_table_prefs',
        'push_subscriptions',
        'tp_absence_reasons',
        'transport_providers',
    ];

    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  list<string>  $groups
     * @return list<array{group: string, table: string, rows: int}>
     */
    public function plan(array $groups): array
    {
        $plan = [];
        foreach ($this->orderedGroups($groups) as $group) {
            foreach (self::GROUPS[$group] as $table) {
                if (! Schema::hasTable($table)) {
                    continue;
                }
                $plan[] = ['group' => $group, 'table' => $table, 'rows' => DB::table($table)->count()];
            }
        }

        return $plan;
    }

    /**
     * User đang mang role driver — sẽ bị gỡ role (giữ tài khoản và các role khác).
     *
     * @return list<array{id: int, name: string, email: string, employee_code: ?string, other_roles: string}>
     */
    public function driverRoleUsers(): array
    {
        if (! Role::query()->where('name', Roles::DRIVER)->exists()) {
            return [];
        }

        return User::query()
            ->role(Roles::DRIVER)
            ->with('roles:id,name')
            ->orderBy('id')
            ->get()
            ->map(fn (User $u) => [
                'id' => (int) $u->id,
                'name' => (string) $u->name,
                'email' => (string) $u->email,
                'employee_code' => $u->employee_code,
                'other_roles' => $u->roles->pluck('name')->reject(fn ($r) => $r === Roles::DRIVER)->implode(', '),
            ])
            ->values()
            ->all();
    }

    /**
     * Bảng có trong DB nhưng không nằm trong nhóm xóa lẫn danh sách giữ — để người chạy tự quyết.
     *
     * @return list<string>
     */
    public function unclassifiedTables(): array
    {
        try {
            $names = collect(Schema::getTables())->pluck('name')->all();
        } catch (\Throwable) {
            return [];
        }

        $known = array_merge(
            self::KEEP_TABLES,
            array_values(config('permission.table_names', [])),
            ...array_values(self::GROUPS),
        );

        return array_values(array_diff($names, $known));
    }

    /**
     * @param  list<string>  $groups
     * @param  bool  $revokeDriverRole  gỡ role driver khỏi mọi user (tài khoản giữ nguyên)
     * @return array{tables: array<string, int>, users: int, files_deleted: int, files_missing: int}
     */
    public function purge(array $groups, bool $revokeDriverRole = true, bool $deleteFiles = true): array
    {
        $groups = $this->orderedGroups($groups);
        $files = $deleteFiles && in_array(self::GROUP_BUSINESS, $groups, true)
            ? $this->collectBusinessFiles()
            : [];

        $result = DB::transaction(function () use ($groups, $revokeDriverRole) {
            $tables = [];
            foreach ($groups as $group) {
                foreach (self::GROUPS[$group] as $table) {
                    if (Schema::hasTable($table)) {
                        $tables[$table] = DB::table($table)->delete();
                    }
                }
            }

            $users = $revokeDriverRole ? $this->revokeDriverRole() : 0;

            $this->audit->log(
                actorId: null,
                event: 'system.test_data_purged',
                metadata: ['groups' => $groups, 'tables' => $tables, 'driver_role_revoked' => $users],
            );

            return ['tables' => $tables, 'users' => $users];
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        [$deleted, $missing] = $this->deleteFiles($files);

        return $result + ['files_deleted' => $deleted, 'files_missing' => $missing];
    }

    /** Gỡ role driver khỏi mọi user; bước import sẽ gán lại cho tài xế thật. */
    private function revokeDriverRole(): int
    {
        $roleIds = Role::query()->where('name', Roles::DRIVER)->pluck('id');
        if ($roleIds->isEmpty()) {
            return 0;
        }

        $revoked = DB::table(config('permission.table_names.model_has_roles'))
            ->where('model_type', (new User)->getMorphClass())
            ->whereIn(config('permission.column_names.role_pivot_key') ?? 'role_id', $roleIds)
            ->delete();

        if (Schema::hasColumn('users', 'primary_role_id')) {
            DB::table('users')->whereIn('primary_role_id', $roleIds)
                ->update(['primary_role_id' => null, 'primary_role_name' => null]);
        }

        return $revoked;
    }

    /**
     * @return list<array{disk: string, path: string}>
     */
    private function collectBusinessFiles(): array
    {
        $files = [];

        if (Schema::hasTable('attachments')) {
            foreach (DB::table('attachments')->select(['id', 'disk', 'path'])->orderBy('id')->cursor() as $a) {
                if ($a->path) {
                    $files[] = ['disk' => $a->disk ?: 'public', 'path' => $a->path];
                }
            }
        }

        foreach (['tp_import_batches', 'dispatch_import_batches'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            foreach (DB::table($table)->select(['id', 'stored_path', 'error_report_path'])->orderBy('id')->cursor() as $b) {
                foreach ([$b->stored_path, $b->error_report_path] as $p) {
                    if ($p) {
                        $files[] = ['disk' => config('filesystems.default'), 'path' => $p];
                    }
                }
            }
        }

        if (Schema::hasTable('trip_costs') && Schema::hasColumn('trip_costs', 'receipt_url')) {
            foreach (DB::table('trip_costs')->whereNotNull('receipt_url')->select(['id', 'receipt_url'])->orderBy('id')->cursor() as $c) {
                $pos = strpos((string) $c->receipt_url, '/storage/');
                if ($pos !== false) {
                    $files[] = ['disk' => 'public', 'path' => substr((string) $c->receipt_url, $pos + strlen('/storage/'))];
                }
            }
        }

        return $files;
    }

    /**
     * @param  list<array{disk: string, path: string}>  $files
     * @return array{0: int, 1: int}
     */
    private function deleteFiles(array $files): array
    {
        $deleted = 0;
        $missing = 0;
        foreach ($files as $f) {
            try {
                $disk = Storage::disk($f['disk']);
                if ($disk->exists($f['path'])) {
                    $disk->delete($f['path']);
                    $deleted++;
                } else {
                    $missing++;
                }
            } catch (\Throwable) {
                $missing++;
            }
        }

        return [$deleted, $missing];
    }

    /**
     * @param  list<string>  $groups
     * @return list<string>
     */
    private function orderedGroups(array $groups): array
    {
        // Business trước fleet/tp_students vì bảng nghiệp vụ tham chiếu xe, tài xế, học sinh.
        return array_values(array_filter(
            array_keys(self::GROUPS),
            fn (string $g) => in_array($g, $groups, true),
        ));
    }
}
