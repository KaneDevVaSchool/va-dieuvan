<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL keeps FK on program_day_id via the old unique index — add the
        // composite unique first, then drop the single-column unique.
        Schema::table('tp_trip_executions', function (Blueprint $table) {
            $table->string('shift', 16)->default('morning')->after('program_day_id');
        });

        Schema::table('tp_trip_executions', function (Blueprint $table) {
            $table->unique(['program_day_id', 'shift'], 'tp_trip_executions_program_day_shift_unique');
        });

        Schema::table('tp_trip_executions', function (Blueprint $table) {
            $table->dropUnique('tp_trip_executions_program_day_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('tp_trip_executions', function (Blueprint $table) {
            $table->unique('program_day_id', 'tp_trip_executions_program_day_id_unique');
        });

        Schema::table('tp_trip_executions', function (Blueprint $table) {
            $table->dropUnique('tp_trip_executions_program_day_shift_unique');
            $table->dropColumn('shift');
        });
    }
};
