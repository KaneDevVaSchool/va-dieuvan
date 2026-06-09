<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tp_day_absences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_day_id')->constrained('tp_program_days')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('tp_students')->cascadeOnDelete();
            $table->enum('shift', ['morning', 'afternoon', 'all'])->default('all');
            $table->enum('absence_type', ['absent', 'parent_notified', 'no_notice', 'late_cancel'])->default('absent');
            $table->enum('category', ['excused', 'unexcused'])->nullable();
            $table->string('reason_code', 50)->nullable();
            $table->text('absence_reason')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('recorded_at')->useCurrent();
            $table->enum('source', ['dispatcher', 'driver', 'system'])->default('dispatcher');
            $table->text('notes')->nullable();

            $table->unique(['program_day_id', 'student_id', 'shift'], 'tp_day_absences_day_student_shift_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tp_day_absences');
    }
};
