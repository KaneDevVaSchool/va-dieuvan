<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tp_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('tp_programs')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('tp_students')->cascadeOnDelete();
            $table->timestamp('enrolled_at')->useCurrent();
            $table->foreignId('enrolled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('unenrolled_at')->nullable();
            $table->foreignId('unenrolled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('unenroll_reason')->nullable();
            $table->text('notes')->nullable();

            $table->unique(['program_id', 'student_id']);
            $table->index(['program_id', 'unenrolled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tp_enrollments');
    }
};
