<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_calendars', function (Blueprint $table) {
            $table->id();
            $table->string('school_year', 9);
            $table->unsignedTinyInteger('semester')->nullable();
            $table->date('date')->unique();
            $table->enum('day_type', ['school_day', 'holiday', 'weekend', 'makeup_day']);
            $table->string('note', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('student_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('route_id')->constrained('routes')->cascadeOnDelete();
            $table->string('school_year', 9);
            $table->unsignedTinyInteger('semester');
            $table->enum('time_slot', ['morning', 'afternoon']);
            $table->foreignId('pickup_point_id')->nullable()->constrained('route_stops')->nullOnDelete();
            $table->foreignId('dropoff_point_id')->nullable()->constrained('route_stops')->nullOnDelete();
            $table->date('effective_from');
            $table->date('effective_to');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->timestamp('deleted_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['route_id', 'time_slot', 'status']);
            $table->unique(
                ['student_id', 'route_id', 'time_slot', 'semester', 'school_year'],
                'student_policies_unique_slot'
            );
        });

        Schema::create('policy_trips', function (Blueprint $table) {
            $table->id();
            $table->date('trip_date');
            $table->enum('time_slot', ['morning', 'afternoon']);
            $table->foreignId('route_id')->constrained('routes')->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->enum('status', ['scheduled', 'assigned', 'in_progress', 'completed', 'cancelled'])->default('scheduled');
            $table->time('planned_departure')->nullable();
            $table->timestamp('actual_departure')->nullable();
            $table->timestamp('actual_arrival')->nullable();
            $table->unsignedSmallInteger('expected_count')->default(0);
            $table->unsignedSmallInteger('boarded_count')->default(0);
            $table->unsignedSmallInteger('absent_count')->default(0);
            $table->json('route_snapshot')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->enum('generated_by', ['system', 'manual'])->default('manual');
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancel_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['trip_date', 'time_slot', 'route_id'], 'policy_trips_date_slot_route');
            $table->index(['trip_date', 'status']);
            $table->index(['driver_id', 'trip_date', 'time_slot']);
        });

        Schema::create('policy_trip_students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_trip_id')->constrained('policy_trips')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('student_policy_id')->constrained('student_policies')->cascadeOnDelete();
            $table->boolean('expected')->default(true);
            $table->timestamp('boarded_at')->nullable();
            $table->foreignId('boarded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('alighted_at')->nullable();
            $table->foreignId('alighted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('absence_reason', ['absent_reported', 'absent_no_notice', 'late_cancellation'])->nullable();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['policy_trip_id', 'student_id'], 'policy_trip_students_trip_student');
        });

        Schema::create('policy_trip_audit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_trip_id')->constrained('policy_trips')->cascadeOnDelete();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('changed_at')->useCurrent();
            $table->enum('action', [
                'created',
                'assigned_driver',
                'status_changed',
                'student_marked_absent',
                'student_boarded',
                'student_alighted',
                'cancelled',
            ]);
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policy_trip_audit');
        Schema::dropIfExists('policy_trip_students');
        Schema::dropIfExists('policy_trips');
        Schema::dropIfExists('student_policies');
        Schema::dropIfExists('school_calendars');
    }
};
