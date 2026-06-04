<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tp_import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('original_filename');
            $table->string('stored_path', 500);
            $table->unsignedInteger('file_size')->nullable();
            $table->enum('file_type', ['xlsx', 'xls', 'csv'])->nullable();
            $table->unsignedInteger('total_rows')->default(0);
            $table->json('header_row')->nullable();
            $table->json('column_mapping')->nullable();
            $table->json('auto_fix_rules')->nullable();
            $table->unsignedInteger('valid_rows')->default(0);
            $table->unsignedInteger('warning_rows')->default(0);
            $table->unsignedInteger('error_rows')->default(0);
            $table->unsignedInteger('imported_rows')->default(0);
            $table->unsignedInteger('skipped_rows')->default(0);
            $table->enum('status', [
                'uploaded', 'parsing', 'parsed', 'mapped', 'importing', 'completed', 'failed',
            ])->default('uploaded');
            $table->foreignId('target_program_id')->nullable()->constrained('tp_programs')->nullOnDelete();
            $table->string('error_report_path', 500)->nullable();
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tp_import_batches');
    }
};
