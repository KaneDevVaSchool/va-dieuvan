<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tp_import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('tp_import_batches')->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->json('raw_data');
            $table->json('mapped_data')->nullable();
            $table->json('fixed_data')->nullable();
            $table->enum('validation_status', ['pending', 'valid', 'warning', 'error'])->default('pending');
            $table->json('validation_errors')->nullable();
            $table->enum('import_status', ['pending', 'imported', 'skipped', 'failed'])->default('pending');
            $table->foreignId('student_id')->nullable()->constrained('tp_students')->nullOnDelete();
            $table->text('import_error')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['batch_id', 'validation_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tp_import_rows');
    }
};
