<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tp_trip_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_day_id')->constrained('tp_program_days')->cascadeOnDelete();
            $table->foreignId('program_id')->constrained('tp_programs')->cascadeOnDelete();

            $table->foreignId('driver_id')->constrained('drivers')->restrictOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->json('driver_snapshot');
            $table->json('vehicle_snapshot')->nullable();

            $table->time('scheduled_time');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();

            $table->decimal('estimated_cost', 12, 2)->nullable();
            $table->decimal('actual_cost', 12, 2)->nullable();
            $table->text('cost_notes')->nullable();
            $table->foreignId('cost_confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cost_confirmed_at')->nullable();

            $table->unsignedSmallInteger('total_expected')->default(0);
            $table->unsignedSmallInteger('total_boarded')->default(0);
            $table->unsignedSmallInteger('total_alighted')->default(0);
            $table->unsignedSmallInteger('total_absent')->default(0);

            $table->enum('status', ['in_progress', 'completed', 'cancelled'])->default('in_progress');
            $table->string('device_id', 100)->nullable();
            $table->unsignedInteger('sync_version')->default(1);
            $table->timestamps();

            $table->unique('program_day_id');
            $table->index(['program_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tp_trip_executions');
    }
};
