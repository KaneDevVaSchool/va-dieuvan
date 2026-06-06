<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const DAY_STUDENT_UNIQUE = 'tp_day_absences_program_day_id_student_id_unique';

    private const DAY_STUDENT_SHIFT_UNIQUE = 'tp_day_absences_day_student_shift_unique';

    public function up(): void
    {
        if (! $this->hasColumn('tp_day_absences', 'shift')) {
            $placeShiftAfterStudent = $this->hasColumn('tp_day_absences', 'student_id');

            Schema::table('tp_day_absences', function (Blueprint $table) use ($placeShiftAfterStudent) {
                $column = $table->enum('shift', ['morning', 'afternoon', 'all'])->default('all');
                if ($placeShiftAfterStudent) {
                    $column->after('student_id');
                }
            });
        }

        if (! $this->indexExists('tp_day_absences', self::DAY_STUDENT_SHIFT_UNIQUE)) {
            $this->removeDuplicateDayAbsencesBeforeShiftUnique();
            $this->dropLegacyDayStudentUnique();

            try {
                Schema::table('tp_day_absences', function (Blueprint $table) {
                    $table->unique(['program_day_id', 'student_id', 'shift'], self::DAY_STUDENT_SHIFT_UNIQUE);
                });
            } catch (\Throwable $e) {
                throw new \RuntimeException(
                    'Could not add unique index '.self::DAY_STUDENT_SHIFT_UNIQUE.' on tp_day_absences: '.$e->getMessage(),
                    (int) $e->getCode(),
                    $e
                );
            }
        }

        if (! $this->hasColumn('tp_program_days', 'morning_attendance_status')) {
            $anchor = $this->programDaysAttendanceAnchorColumn();

            Schema::table('tp_program_days', function (Blueprint $table) use ($anchor) {
                $status = $table->enum('morning_attendance_status', ['not_started', 'draft', 'confirmed'])->nullable();
                if ($anchor !== null) {
                    $status->after($anchor);
                }
                $table->timestamp('morning_attendance_confirmed_at')->nullable()->after('morning_attendance_status');
                $table->foreignId('morning_attendance_confirmed_by')->nullable()->after('morning_attendance_confirmed_at')
                    ->constrained('users')->nullOnDelete();
                $table->unsignedInteger('morning_attendance_lock_version')->default(0)->after('morning_attendance_confirmed_by');
            });
        }

        if (! $this->hasColumn('tp_program_days', 'afternoon_attendance_status')) {
            $afternoonAnchor = $this->hasColumn('tp_program_days', 'morning_attendance_lock_version')
                ? 'morning_attendance_lock_version'
                : ($this->hasColumn('tp_program_days', 'attendance_lock_version') ? 'attendance_lock_version' : null);

            Schema::table('tp_program_days', function (Blueprint $table) use ($afternoonAnchor) {
                $status = $table->enum('afternoon_attendance_status', ['not_started', 'draft', 'confirmed'])->nullable();
                if ($afternoonAnchor !== null) {
                    $status->after($afternoonAnchor);
                }
                $table->timestamp('afternoon_attendance_confirmed_at')->nullable()->after('afternoon_attendance_status');
                $table->foreignId('afternoon_attendance_confirmed_by')->nullable()->after('afternoon_attendance_confirmed_at')
                    ->constrained('users')->nullOnDelete();
                $table->unsignedInteger('afternoon_attendance_lock_version')->default(0)->after('afternoon_attendance_confirmed_by');
            });
        }
    }

    public function down(): void
    {
        if ($this->hasColumn('tp_program_days', 'morning_attendance_status')) {
            Schema::table('tp_program_days', function (Blueprint $table) {
                if ($this->hasColumn('tp_program_days', 'morning_attendance_confirmed_by')) {
                    $table->dropConstrainedForeignId('morning_attendance_confirmed_by');
                }
                if ($this->hasColumn('tp_program_days', 'afternoon_attendance_confirmed_by')) {
                    $table->dropConstrainedForeignId('afternoon_attendance_confirmed_by');
                }
                $table->dropColumn([
                    'morning_attendance_status',
                    'morning_attendance_confirmed_at',
                    'morning_attendance_lock_version',
                    'afternoon_attendance_status',
                    'afternoon_attendance_confirmed_at',
                    'afternoon_attendance_lock_version',
                ]);
            });
        }

        if ($this->hasColumn('tp_day_absences', 'shift')) {
            if ($this->indexExists('tp_day_absences', self::DAY_STUDENT_SHIFT_UNIQUE)) {
                Schema::table('tp_day_absences', function (Blueprint $table) {
                    $table->dropUnique(self::DAY_STUDENT_SHIFT_UNIQUE);
                });
            }

            Schema::table('tp_day_absences', function (Blueprint $table) {
                $table->dropColumn('shift');
            });

            if (! $this->indexExists('tp_day_absences', self::DAY_STUDENT_UNIQUE)) {
                Schema::table('tp_day_absences', function (Blueprint $table) {
                    $table->unique(['program_day_id', 'student_id'], self::DAY_STUDENT_UNIQUE);
                });
            }
        }
    }

    private function programDaysAttendanceAnchorColumn(): ?string
    {
        if ($this->hasColumn('tp_program_days', 'attendance_lock_version')) {
            return 'attendance_lock_version';
        }

        if ($this->hasColumn('tp_program_days', 'notes')) {
            return 'notes';
        }

        return null;
    }

    private function dropLegacyDayStudentUnique(): void
    {
        if ($this->indexExists('tp_day_absences', self::DAY_STUDENT_UNIQUE)) {
            try {
                Schema::table('tp_day_absences', function (Blueprint $table) {
                    $table->dropUnique(self::DAY_STUDENT_UNIQUE);
                });
            } catch (\Throwable) {
                // Partial rerun.
            }

            return;
        }

        try {
            Schema::table('tp_day_absences', function (Blueprint $table) {
                $table->dropUnique(['program_day_id', 'student_id']);
            });
        } catch (\Throwable) {
            // Unique already removed (e.g. partial rerun).
        }
    }

    private function removeDuplicateDayAbsencesBeforeShiftUnique(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('
                DELETE a FROM tp_day_absences AS a
                INNER JOIN tp_day_absences AS b
                    ON a.program_day_id = b.program_day_id
                    AND a.student_id = b.student_id
                    AND a.id > b.id
            ');

            return;
        }

        if ($driver === 'sqlite') {
            DB::statement('
                DELETE FROM tp_day_absences
                WHERE id NOT IN (
                    SELECT MIN(id)
                    FROM tp_day_absences
                    GROUP BY program_day_id, student_id
                )
            ');
        }
    }

    private function hasColumn(string $table, string $column): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'mysql') {
            try {
                $prefixedTable = str_replace('`', '``', $connection->getTablePrefix().$table);
                $rows = DB::select(
                    "SHOW COLUMNS FROM `{$prefixedTable}` LIKE ?",
                    [$column]
                );

                return count($rows) > 0;
            } catch (\Throwable) {
                return Schema::hasColumn($table, $column);
            }
        }

        return Schema::hasColumn($table, $column);
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        try {
            if ($driver === 'sqlite') {
                $quotedTable = '"'.str_replace('"', '""', $table).'"';
                $rows = DB::select("PRAGMA index_list({$quotedTable})");

                foreach ($rows as $row) {
                    if (($row->name ?? null) === $indexName) {
                        return true;
                    }
                }

                return false;
            }

            if ($driver === 'mysql') {
                $prefixedTable = str_replace('`', '``', $connection->getTablePrefix().$table);
                $rows = DB::select(
                    "SHOW INDEX FROM `{$prefixedTable}` WHERE Key_name = ?",
                    [$indexName]
                );

                return count($rows) > 0;
            }
        } catch (\Throwable) {
            return false;
        }

        return false;
    }
};
