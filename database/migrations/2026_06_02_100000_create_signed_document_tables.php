<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signed_document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispatch_request_id')->constrained('dispatch_requests')->cascadeOnDelete();
            $table->foreignId('attachment_id')->constrained('attachments')->restrictOnDelete();
            $table->unsignedInteger('version_no');
            $table->boolean('is_current')->default(true);

            $table->string('source_pdf_sha256', 64)->nullable();

            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('uploaded_at');

            $table->string('ocr_status', 32)->default('pending');
            $table->timestamp('ocr_started_at')->nullable();
            $table->timestamp('ocr_completed_at')->nullable();
            $table->text('ocr_error')->nullable();

            $table->boolean('signature_detected')->nullable();
            $table->decimal('signature_score', 5, 4)->nullable();
            $table->json('signature_regions')->nullable();
            $table->boolean('signature_verified')->default(false);
            $table->string('verification_status', 32)->default('pending');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_note')->nullable();

            $table->timestamps();

            $table->unique(['dispatch_request_id', 'version_no'], 'sdv_dr_version_uniq');
            $table->index(['dispatch_request_id', 'is_current'], 'sdv_dr_current_idx');
            $table->index(['verification_status', 'ocr_status'], 'sdv_verif_ocr_idx');
        });

        Schema::create('signed_document_verifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('signed_document_version_id');
            $table->foreign('signed_document_version_id', 'sdver_log_version_fk')
                ->references('id')
                ->on('signed_document_versions')
                ->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 32);
            $table->string('before_status', 32)->nullable();
            $table->string('after_status', 32);
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['signed_document_version_id', 'created_at'], 'sdver_version_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signed_document_verifications');
        Schema::dropIfExists('signed_document_versions');
    }
};
