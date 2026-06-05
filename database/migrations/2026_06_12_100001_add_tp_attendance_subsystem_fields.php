<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tp_absence_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('label_vi', 120);
            $table->enum('default_category', ['excused', 'unexcused'])->default('excused');
            $table->boolean('active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
        });

        Schema::table('tp_program_days', function (Blueprint $table) {
            $table->enum('attendance_status', ['not_started', 'draft', 'confirmed'])
                ->default('not_started')
                ->after('notes');
            $table->timestamp('attendance_confirmed_at')->nullable()->after('attendance_status');
            $table->foreignId('attendance_confirmed_by')->nullable()->after('attendance_confirmed_at')
                ->constrained('users')->nullOnDelete();
            $table->unsignedInteger('attendance_lock_version')->default(0)->after('attendance_confirmed_by');
        });

        Schema::table('tp_day_absences', function (Blueprint $table) {
            $table->enum('category', ['excused', 'unexcused'])->nullable()->after('absence_type');
            $table->string('reason_code', 50)->nullable()->after('category');
        });

        Schema::table('tp_enrollments', function (Blueprint $table) {
            $table->string('pickup_point', 255)->nullable()->after('notes');
        });

        DB::table('tp_day_absences')->whereIn('absence_type', ['parent_notified', 'late_cancel'])
            ->update(['category' => 'excused']);
        DB::table('tp_day_absences')->whereIn('absence_type', ['no_notice', 'absent'])
            ->update(['category' => 'unexcused']);
        DB::table('tp_day_absences')->whereNull('category')
            ->update(['category' => 'unexcused']);
    }

    public function down(): void
    {
        Schema::table('tp_enrollments', function (Blueprint $table) {
            $table->dropColumn('pickup_point');
        });

        Schema::table('tp_day_absences', function (Blueprint $table) {
            $table->dropColumn(['category', 'reason_code']);
        });

        Schema::table('tp_program_days', function (Blueprint $table) {
            $table->dropConstrainedForeignId('attendance_confirmed_by');
            $table->dropColumn([
                'attendance_status',
                'attendance_confirmed_at',
                'attendance_lock_version',
            ]);
        });

        Schema::dropIfExists('tp_absence_reasons');
    }
};
