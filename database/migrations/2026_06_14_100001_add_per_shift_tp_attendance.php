<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tp_day_absences', function (Blueprint $table) {
            $table->enum('shift', ['morning', 'afternoon', 'all'])->default('all')->after('student_id');
        });

        Schema::table('tp_day_absences', function (Blueprint $table) {
            $table->dropUnique(['program_day_id', 'student_id']);
            $table->unique(['program_day_id', 'student_id', 'shift'], 'tp_day_absences_day_student_shift_unique');
        });

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

        DB::table('tp_day_absences')->whereNull('shift')->update(['shift' => 'all']);
    }

    public function down(): void
    {
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

        Schema::table('tp_day_absences', function (Blueprint $table) {
            $table->dropUnique('tp_day_absences_day_student_shift_unique');
            $table->dropColumn('shift');
            $table->unique(['program_day_id', 'student_id']);
        });
    }
};
