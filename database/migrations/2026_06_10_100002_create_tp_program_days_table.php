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
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();

            $table->decimal('estimated_cost', 12, 2)->nullable();
            $table->text('cancel_reason')->nullable();
            $table->text('notes')->nullable();
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
