<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tp_trip_student_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('execution_id')->constrained('tp_trip_executions')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('tp_students')->cascadeOnDelete();
            $table->json('student_snapshot');

            $table->enum('initial_status', ['expected', 'pre_absent'])->default('expected');
            $table->timestamp('boarded_at')->nullable();
            $table->foreignId('boarded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('alighted_at')->nullable();
            $table->foreignId('alighted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('absent_at')->nullable();
            $table->foreignId('absent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('absence_type', ['parent_notified', 'no_notice', 'late_cancel'])->nullable();
            $table->text('absence_notes')->nullable();

            $table->enum('final_status', ['pending', 'boarded', 'alighted', 'absent'])->default('pending');
            $table->timestamp('client_timestamp')->nullable();
            $table->enum('sync_status', ['synced', 'pending_sync', 'conflict', 'timestamp_suspect'])->default('synced');
            $table->timestamps();

            $table->unique(['execution_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tp_trip_student_logs');
    }
};
