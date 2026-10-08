<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hồ sơ chi phí xe: Phiếu đề xuất (DX), Đề nghị thanh toán / tạm ứng / hoàn ứng (TT/TU/HU), Bảng kê không hóa đơn (BK).
        Schema::create('expense_documents', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('doc_type', 32);
            $table->string('payment_kind', 16)->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('expense_documents')->nullOnDelete();

            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->foreignId('requester_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();

            // Snapshot người đề nghị — tài xế tự sửa được.
            $table->string('requester_name')->nullable();
            $table->string('requester_email')->nullable();
            $table->string('requester_phone', 32)->nullable();
            $table->string('department_label')->nullable();

            $table->text('purpose')->nullable();
            $table->text('basis')->nullable();
            $table->date('proposed_on')->nullable();
            $table->date('needed_by')->nullable();
            $table->boolean('is_urgent')->default(false);
            $table->string('urgent_reason')->nullable();
            $table->string('allocation_target')->nullable();
            $table->string('allocation_area')->nullable();
            $table->string('handover_person')->nullable();
            $table->string('purchasing_person')->nullable();

            $table->decimal('total_amount', 14, 2)->default(0);
            $table->string('amount_in_words')->nullable();
            $table->string('payment_due_label')->nullable();
            $table->string('payment_method', 16)->nullable();
            $table->string('bank_account_no', 64)->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_holder')->nullable();
            $table->text('note')->nullable();

            $table->json('signatories')->nullable();

            $table->string('status', 16)->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('rejected_reason')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();

            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'doc_type', 'created_at']);
            $table->index(['driver_id', 'status']);
            $table->index(['requester_user_id', 'status']);
            $table->index(['vehicle_id', 'doc_type', 'proposed_on']);
        });

        Schema::create('expense_document_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('expense_documents')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->foreignId('trip_cost_id')->nullable()->constrained('trip_costs')->nullOnDelete();
            // true khi dòng trip_cost được tạo từ chính hồ sơ này (xóa dòng nháp → xóa luôn trip_cost).
            $table->boolean('owns_trip_cost')->default(false);
            $table->string('cost_type', 64)->nullable();
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->string('unit', 32)->nullable();
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('unit_price', 14, 2)->default(0);
            $table->decimal('amount', 14, 2)->default(0);
            $table->date('purchased_on')->nullable();
            $table->string('seller_name')->nullable();
            $table->string('seller_address')->nullable();
            $table->boolean('has_invoice')->default(true);
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['document_id', 'sort_order']);
            $table->index(['vehicle_id', 'purchased_on']);
        });

        // Một dòng duy nhất: thông tin công ty, mã biểu mẫu, người ký mặc định theo loại chứng từ.
        Schema::create('expense_form_settings', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->nullable();
            $table->string('tax_code', 32)->nullable();
            $table->string('company_address')->nullable();
            $table->string('purchasing_place')->nullable();
            $table->string('city_label')->nullable();
            $table->string('default_department_label')->nullable();
            $table->json('form_codes')->nullable();
            $table->json('default_signatories')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_form_settings');
        Schema::dropIfExists('expense_document_lines');
        Schema::dropIfExists('expense_documents');
    }
};
