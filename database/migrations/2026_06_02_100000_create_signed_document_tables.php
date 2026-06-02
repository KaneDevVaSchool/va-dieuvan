<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schemaComplete = Schema::hasTable('signed_document_verifications')
            && Schema::hasColumn('dispatch_requests', 'signing_workflow_status')
            && Schema::hasColumn('attachments', 'sha256');

        if ($schemaComplete) {
            return;
        }

        // Recover from a prior failed run (tables created, migration not recorded).
        if (Schema::hasTable('signed_document_versions')) {
            Schema::dropIfExists('signed_document_verifications');
            Schema::dropIfExists('signed_document_versions');
        }

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

        Schema::table('dispatch_requests', function (Blueprint $table) {
            $table->string('signing_workflow_status', 32)->nullable()->after('paper_reference');
            $table->foreignId('current_signed_version_id')->nullable()->after('signing_workflow_status');
            $table->timestamp('signed_at')->nullable()->after('current_signed_version_id');
            $table->foreignId('signed_by')->nullable()->after('signed_at')->constrained('users')->nullOnDelete();
            $table->boolean('signature_detected')->nullable()->after('signed_by');
            $table->boolean('signature_verified')->nullable()->after('signature_detected');
            $table->string('verification_status', 32)->nullable()->after('signature_verified');
        });

        Schema::table('attachments', function (Blueprint $table) {
            $table->string('sha256', 64)->nullable()->after('mime_type');
            $table->unsignedBigInteger('signed_document_version_id')->nullable()->after('sha256');
        });

        Schema::table('dispatch_requests', function (Blueprint $table) {
            $table->foreign('current_signed_version_id')
                ->references('id')
                ->on('signed_document_versions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('dispatch_requests', function (Blueprint $table) {
            $table->dropForeign(['current_signed_version_id']);
            $table->dropForeign(['signed_by']);
            $table->dropColumn([
                'signing_workflow_status',
                'current_signed_version_id',
                'signed_at',
                'signed_by',
                'signature_detected',
                'signature_verified',
                'verification_status',
            ]);
        });

        Schema::table('attachments', function (Blueprint $table) {
            $table->dropColumn(['sha256', 'signed_document_version_id']);
        });

        Schema::dropIfExists('signed_document_verifications');
        Schema::dropIfExists('signed_document_versions');
    }
};
