<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatch_import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('original_filename');
            $table->string('stored_path', 500);
            $table->unsignedInteger('file_size')->nullable();
            $table->json('selected_sheets')->nullable();
            $table->enum('status', [
                'uploaded',
                'analyzed',
                'executing',
                'completed',
                'failed',
            ])->default('uploaded');
            $table->json('analyze_stats')->nullable();
            $table->json('execute_stats')->nullable();
            $table->json('issues')->nullable();
            $table->unsignedInteger('issue_count')->default(0);
            $table->string('error_report_path', 500)->nullable();
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_import_batches');
    }
};
