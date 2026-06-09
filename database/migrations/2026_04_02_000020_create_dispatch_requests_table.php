<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatch_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source_channel', 32)->default('portal');
            $table->boolean('is_urgent')->default(false);
            $table->text('urgent_reason')->nullable();
            $table->enum('urgent_trigger', ['auto', 'manual'])->nullable();

            $table->enum('trip_type', ['door_to_door', 'point_to_point', 'business', 'cargo']);
            $table->string('origin')->nullable();
            $table->string('destination')->nullable();
            $table->dateTime('depart_at');
            $table->dateTime('arrive_by')->nullable();

            $table->unsignedSmallInteger('passenger_count')->nullable();
            $table->unsignedSmallInteger('student_count_actual')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('student_count_submitted_at')->nullable();
            $table->foreignId('student_count_submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->json('wizard_snapshot')->nullable();
            $table->decimal('service_price', 12, 2)->nullable();
            $table->foreignId('price_filled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('price_filled_at')->nullable();
            $table->foreignId('assigned_dept_head_id')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('status', ['draft', 'pending', 'price_filled', 'approved', 'rejected', 'cancelled'])->default('draft');
            $table->string('rejection_reason')->nullable();

            $table->enum('paper_status', ['pending', 'received', 'digitally_signed'])->default('pending');
            $table->dateTime('paper_received_at')->nullable();
            $table->string('paper_reference')->nullable();
            $table->string('signing_workflow_status', 32)->nullable();
            $table->unsignedBigInteger('current_signed_version_id')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->foreignId('signed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('signature_detected')->nullable();
            $table->boolean('signature_verified')->nullable();
            $table->string('verification_status', 32)->nullable();

            $table->unsignedBigInteger('dispatch_request_template_id')->nullable();
            $table->foreignId('cloned_from_id')->nullable()->constrained('dispatch_requests')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['requester_id', 'depart_at']);
            $table->index(['status', 'depart_at']);
            $table->index(['trip_type', 'depart_at'], 'dr_triptype_depart_idx');
            $table->index(['source_channel', 'depart_at'], 'dr_source_depart_idx');
            $table->index(['paper_status', 'paper_received_at'], 'dr_paper_status_received_idx');
            $table->index(['is_urgent', 'depart_at'], 'dr_urgent_depart_idx');
            $table->index(['dispatch_request_template_id', 'depart_at'], 'dispatch_requests_template_depart_idx');
            $table->index('cloned_from_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_requests');
    }
};
