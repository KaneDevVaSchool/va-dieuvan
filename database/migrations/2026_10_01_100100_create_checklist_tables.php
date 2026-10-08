<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checklist_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('name');
            $table->string('frequency', 16);
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_active')->default(true);
            $table->json('form_meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('checklist_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('checklist_templates')->cascadeOnDelete();
            $table->string('group_name', 120);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('title');
            $table->text('requirement')->nullable();
            $table->string('severity', 16)->default('non_critical');
            $table->text('ng_action')->nullable();
            $table->boolean('allow_na')->default(false);
            $table->boolean('requires_photo_on_ng')->default(false);
            $table->string('auto_source', 64)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['template_id', 'is_active', 'sort_order'], 'checklist_items_template_active_sort_idx');
        });

        Schema::create('checklist_reminder_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('checklist_templates')->cascadeOnDelete();
            // HH:MM theo giờ Việt Nam.
            $table->string('remind_at', 5);
            // ISO weekday 1 (T2) … 7 (CN).
            $table->json('weekdays');
            $table->unsignedInteger('escalate_after_minutes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'remind_at']);
        });

        Schema::create('checklist_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->foreignId('template_id')->constrained('checklist_templates')->restrictOnDelete();
            $table->unsignedInteger('template_version')->default(1);
            $table->foreignId('driver_id')->constrained('drivers')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->foreignId('trip_id')->nullable()->constrained('trips')->nullOnDelete();
            $table->foreignId('tp_program_day_id')->nullable()->constrained('tp_program_days')->nullOnDelete();

            // daily → Y-m-d, weekly → o-\WW (ISO week) theo giờ Việt Nam.
            $table->string('period_type', 16);
            $table->string('period_key', 16);

            $table->timestamp('started_at')->nullable();
            // Giờ server — mốc thời gian chính thức. client_submitted_at chỉ để đối chiếu.
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('client_submitted_at')->nullable();

            $table->string('location_text')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();

            $table->json('trip_info')->nullable();
            $table->string('result', 16)->nullable();
            $table->unsignedInteger('critical_ng_count')->default(0);
            $table->unsignedInteger('non_critical_ng_count')->default(0);
            $table->text('note')->nullable();
            $table->string('inspector_name')->nullable();
            $table->timestamp('confirmed_statement_at')->nullable();

            $table->string('status', 16)->default('draft');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();

            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'period_type', 'period_key'], 'checklist_sub_status_period_idx');
            $table->index(['driver_id', 'period_key']);
            $table->index(['vehicle_id', 'submitted_at']);
            $table->index(['result', 'submitted_at']);
        });

        Schema::create('checklist_submission_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('checklist_submissions')->cascadeOnDelete();
            $table->foreignId('template_item_id')->nullable()->constrained('checklist_template_items')->nullOnDelete();
            // Snapshot nội dung mục tại thời điểm tạo phiếu (sửa template không làm đổi lịch sử).
            $table->string('group_name', 120);
            $table->string('title');
            $table->text('requirement')->nullable();
            $table->string('severity', 16);
            $table->text('ng_action')->nullable();
            $table->boolean('allow_na')->default(false);
            $table->boolean('requires_photo_on_ng')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('result', 8)->nullable();
            $table->text('note')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->json('prefill_hint')->nullable();
            $table->timestamps();

            $table->unique(['submission_id', 'template_item_id']);
            $table->index(['submission_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_submission_items');
        Schema::dropIfExists('checklist_submissions');
        Schema::dropIfExists('checklist_reminder_slots');
        Schema::dropIfExists('checklist_template_items');
        Schema::dropIfExists('checklist_templates');
    }
};
