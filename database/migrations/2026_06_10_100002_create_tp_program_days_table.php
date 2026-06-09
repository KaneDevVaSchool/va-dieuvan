<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tp_program_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('tp_programs')->cascadeOnDelete();
            $table->date('scheduled_date');
            $table->enum('day_type', ['operating', 'cancelled', 'makeup'])->default('operating');
            $table->unsignedSmallInteger('expected_count')->default(0);

            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->foreignId('backup_driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('backup_assigned_at')->nullable();
            $table->foreignId('backup_assigned_by')->nullable()->constrained('users')->nullOnDelete();

            $table->foreignId('morning_driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->foreignId('morning_backup_driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->foreignId('afternoon_driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->foreignId('afternoon_backup_driver_id')->nullable()->constrained('drivers')->nullOnDelete();

            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('confirmed_by_driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->timestamp('morning_confirmed_at')->nullable();
            $table->foreignId('morning_confirmed_by_driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->timestamp('afternoon_confirmed_at')->nullable();
            $table->foreignId('afternoon_confirmed_by_driver_id')->nullable()->constrained('drivers')->nullOnDelete();

            $table->decimal('estimated_cost', 12, 2)->nullable();
            $table->text('cancel_reason')->nullable();
            $table->text('notes')->nullable();

            $table->enum('attendance_status', ['not_started', 'draft', 'confirmed'])->default('not_started');
            $table->timestamp('attendance_confirmed_at')->nullable();
            $table->foreignId('attendance_confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('attendance_lock_version')->default(0);

            $table->enum('morning_attendance_status', ['not_started', 'draft', 'confirmed'])->nullable();
            $table->timestamp('morning_attendance_confirmed_at')->nullable();
            $table->foreignId('morning_attendance_confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('morning_attendance_lock_version')->default(0);

            $table->enum('afternoon_attendance_status', ['not_started', 'draft', 'confirmed'])->nullable();
            $table->timestamp('afternoon_attendance_confirmed_at')->nullable();
            $table->foreignId('afternoon_attendance_confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('afternoon_attendance_lock_version')->default(0);

            $table->timestamps();

            $table->unique(['program_id', 'scheduled_date']);
            $table->index(['program_id', 'scheduled_date']);
            $table->index('scheduled_date');
            $table->index('driver_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tp_program_days');
    }
};
