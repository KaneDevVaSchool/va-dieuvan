<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campuses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name', 200);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'name']);
        });

        Schema::create('academic_terms', function (Blueprint $table) {
            $table->id();
            $table->string('academic_year', 20);
            $table->string('term_code', 10);
            $table->string('name', 120);
            $table->date('starts_on');
            $table->date('ends_on');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['academic_year', 'term_code']);
            $table->index(['is_active', 'starts_on']);
        });

        Schema::create('p2p_policy_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_term_id')->constrained('academic_terms')->cascadeOnDelete();
            $table->date('operating_from');
            $table->date('operating_to');
            $table->time('default_morning_start')->default('06:00:00');
            $table->time('default_morning_end')->default('07:00:00');
            $table->time('default_afternoon_start')->default('15:30:00');
            $table->time('default_afternoon_end')->default('16:30:00');
            $table->unsignedTinyInteger('weekdays_mask')->default(31);
            $table->enum('status', ['draft', 'generating', 'active', 'closed'])->default('draft');
            $table->dateTime('activated_at')->nullable();
            $table->foreignId('activated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('generation_run_id')->nullable();
            $table->timestamps();

            $table->index(['status', 'operating_from', 'operating_to']);
        });

        Schema::create('policy_term_holidays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('p2p_policy_term_id')->constrained('p2p_policy_terms')->cascadeOnDelete();
            $table->date('holiday_date');
            $table->string('label', 200)->nullable();
            $table->timestamps();

            $table->unique(['p2p_policy_term_id', 'holiday_date']);
        });

        Schema::create('policy_term_skip_dates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('p2p_policy_term_id')->constrained('p2p_policy_terms')->cascadeOnDelete();
            $table->date('skip_date');
            $table->string('reason', 500)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['p2p_policy_term_id', 'skip_date']);
        });

        Schema::create('policy_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('p2p_policy_term_id')->constrained('p2p_policy_terms')->cascadeOnDelete();
            $table->string('name', 200);
            $table->foreignId('origin_campus_id')->constrained('campuses');
            $table->foreignId('dest_campus_id')->constrained('campuses');
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->foreignId('backup_driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->time('morning_start')->nullable();
            $table->time('morning_end')->nullable();
            $table->time('afternoon_start')->nullable();
            $table->time('afternoon_end')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['p2p_policy_term_id', 'is_active']);
        });

        Schema::create('policy_students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_route_id')->constrained('policy_routes')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('student_code', 20);
            $table->string('student_name', 200);
            $table->string('class_name', 20)->nullable();
            $table->enum('direction', ['one_way', 'two_way'])->default('two_way');
            $table->string('policy_type', 100);
            $table->text('policy_note')->nullable();
            $table->string('contract_number', 50)->nullable();
            $table->string('sbs_contract', 50)->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('active_weekdays_mask')->nullable();
            $table->string('imported_from', 200)->nullable();
            $table->timestamps();

            $table->unique(['policy_route_id', 'student_id', 'effective_from'], 'uq_policy_route_student_effective');
            $table->index(['policy_route_id', 'is_active']);
            $table->index(['student_id']);
            $table->index(['class_name']);
        });

        Schema::create('policy_generation_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('p2p_policy_term_id')->constrained('p2p_policy_terms')->cascadeOnDelete();
            $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending');
            $table->unsignedInteger('total_days')->default(0);
            $table->unsignedInteger('processed_days')->default(0);
            $table->unsignedInteger('total_slots')->default(0);
            $table->unsignedInteger('created_slots')->default(0);
            $table->unsignedInteger('skipped_slots')->default(0);
            $table->text('error_message')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['p2p_policy_term_id', 'status']);
        });

        Schema::table('p2p_policy_terms', function (Blueprint $table) {
            $table->foreign('generation_run_id')->references('id')->on('policy_generation_runs')->nullOnDelete();
        });

        Schema::create('policy_trip_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('p2p_policy_term_id')->constrained('p2p_policy_terms')->cascadeOnDelete();
            $table->foreignId('policy_route_id')->constrained('policy_routes')->cascadeOnDelete();
            $table->date('run_date');
            $table->enum('leg', ['morning', 'afternoon']);
            $table->foreignId('dispatch_request_id')->nullable()->constrained('dispatch_requests')->nullOnDelete();
            $table->foreignId('trip_id')->nullable()->constrained('trips')->nullOnDelete();
            $table->timestamps();

            $table->unique(['p2p_policy_term_id', 'policy_route_id', 'run_date', 'leg'], 'uq_policy_trip_slot');
            $table->index(['p2p_policy_term_id', 'run_date']);
        });
    }

    public function down(): void
    {
        Schema::table('p2p_policy_terms', function (Blueprint $table) {
            $table->dropForeign(['generation_run_id']);
        });

        Schema::dropIfExists('policy_trip_slots');
        Schema::dropIfExists('policy_generation_runs');
        Schema::dropIfExists('policy_students');
        Schema::dropIfExists('policy_routes');
        Schema::dropIfExists('policy_term_skip_dates');
        Schema::dropIfExists('policy_term_holidays');
        Schema::dropIfExists('p2p_policy_terms');
        Schema::dropIfExists('academic_terms');
        Schema::dropIfExists('campuses');
    }
};
