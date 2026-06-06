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
        if (! Schema::hasColumn('tp_day_absences', 'shift')) {
            Schema::table('tp_day_absences', function (Blueprint $table) {
                $table->enum('shift', ['morning', 'afternoon', 'all'])->default('all')->after('student_id');
            });
        }

        if (! $this->indexExists('tp_day_absences', self::DAY_STUDENT_SHIFT_UNIQUE)) {
            $this->removeDuplicateDayAbsencesBeforeShiftUnique();
            $this->dropLegacyDayStudentUnique();

            Schema::table('tp_day_absences', function (Blueprint $table) {
                $table->unique(['program_day_id', 'student_id', 'shift'], self::DAY_STUDENT_SHIFT_UNIQUE);
            });
        }

        if (! Schema::hasColumn('tp_program_days', 'morning_attendance_status')) {
            Schema::table('tp_program_days', function (Blueprint $table) {
                $table->enum('morning_attendance_status', ['not_started', 'draft', 'confirmed'])
                    ->nullable()
                    ->after('attendance_lock_version');
                $table->timestamp('morning_attendance_confirmed_at')->nullable()->after('morning_attendance_status');
                $table->foreignId('morning_attendance_confirmed_by')->nullable()->after('morning_attendance_confirmed_at')
                    ->constrained('users')->nullOnDelete();
                $table->unsignedInteger('morning_attendance_lock_version')->default(0)->after('morning_attendance_confirmed_by');

                $table->enum('afternoon_attendance_status', ['not_started', 'draft', 'confirmed'])
                    ->nullable()
                    ->after('morning_attendance_lock_version');
                $table->timestamp('afternoon_attendance_confirmed_at')->nullable()->after('afternoon_attendance_status');
                $table->foreignId('afternoon_attendance_confirmed_by')->nullable()->after('afternoon_attendance_confirmed_at')
                    ->constrained('users')->nullOnDelete();
                $table->unsignedInteger('afternoon_attendance_lock_version')->default(0)->after('afternoon_attendance_confirmed_by');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tp_program_days', 'morning_attendance_status')) {
            Schema::table('tp_program_days', function (Blueprint $table) {
                $table->dropConstrainedForeignId('morning_attendance_confirmed_by');
                $table->dropConstrainedForeignId('afternoon_attendance_confirmed_by');
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

        if (Schema::hasColumn('tp_day_absences', 'shift')) {
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

    private function dropLegacyDayStudentUnique(): void
    {
        if ($this->indexExists('tp_day_absences', self::DAY_STUDENT_UNIQUE)) {
            Schema::table('tp_day_absences', function (Blueprint $table) {
                $table->dropUnique(self::DAY_STUDENT_UNIQUE);
            });

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

    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

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

        return false;
    }
};
