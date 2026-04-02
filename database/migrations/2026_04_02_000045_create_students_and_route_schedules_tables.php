<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('student_code')->nullable()->unique();
            $table->string('full_name');
            $table->date('date_of_birth')->nullable();
            $table->string('grade')->nullable();
            $table->string('guardian_name')->nullable();
            $table->string('guardian_phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('route_students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained('routes')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->timestamps();

            $table->unique(['route_id', 'student_id']);
            $table->index(['route_id', 'starts_on']);
        });

        Schema::create('route_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_version_id')->constrained('route_versions')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // 1=Mon .. 7=Sun
            $table->time('depart_time');
            $table->time('arrive_time')->nullable();
            $table->timestamps();

            $table->unique(['route_version_id', 'day_of_week', 'depart_time']);
        });

        Schema::create('route_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_version_id')->constrained('route_versions')->cascadeOnDelete();
            $table->date('run_date');
            $table->foreignId('trip_id')->nullable()->constrained('trips')->nullOnDelete();
            $table->timestamps();

            $table->unique(['route_version_id', 'run_date']);
            $table->index(['run_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_runs');
        Schema::dropIfExists('route_schedules');
        Schema::dropIfExists('route_students');
        Schema::dropIfExists('students');
    }
};

