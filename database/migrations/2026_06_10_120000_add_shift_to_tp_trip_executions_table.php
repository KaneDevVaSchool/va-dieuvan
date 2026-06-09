<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function indexExists(string $table, string $indexName): bool
    {
        $rows = DB::select(
            'SHOW INDEX FROM `'.$table.'` WHERE Key_name = ?',
            [$indexName],
        );

        return count($rows) > 0;
    }

    public function up(): void
    {
        // MySQL keeps FK on program_day_id via the old unique index — add the
        // composite unique first, then drop the single-column unique.
        // Steps are idempotent for partial reruns after a failed migrate.
        if (! Schema::hasColumn('tp_trip_executions', 'shift')) {
            Schema::table('tp_trip_executions', function (Blueprint $table) {
                $table->string('shift', 16)->default('morning')->after('program_day_id');
            });
        }

        if (! $this->indexExists('tp_trip_executions', 'tp_trip_executions_program_day_shift_unique')) {
            Schema::table('tp_trip_executions', function (Blueprint $table) {
                $table->unique(['program_day_id', 'shift'], 'tp_trip_executions_program_day_shift_unique');
            });
        }

        if ($this->indexExists('tp_trip_executions', 'tp_trip_executions_program_day_id_unique')) {
            Schema::table('tp_trip_executions', function (Blueprint $table) {
                $table->dropUnique('tp_trip_executions_program_day_id_unique');
            });
        }
    }

    public function down(): void
    {
        if (! $this->indexExists('tp_trip_executions', 'tp_trip_executions_program_day_id_unique')) {
            Schema::table('tp_trip_executions', function (Blueprint $table) {
                $table->unique('program_day_id', 'tp_trip_executions_program_day_id_unique');
            });
        }

        if ($this->indexExists('tp_trip_executions', 'tp_trip_executions_program_day_shift_unique')) {
            Schema::table('tp_trip_executions', function (Blueprint $table) {
                $table->dropUnique('tp_trip_executions_program_day_shift_unique');
            });
        }

        if (Schema::hasColumn('tp_trip_executions', 'shift')) {
            Schema::table('tp_trip_executions', function (Blueprint $table) {
                $table->dropColumn('shift');
            });
        }
    }
};
