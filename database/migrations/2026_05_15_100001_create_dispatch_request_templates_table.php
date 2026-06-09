<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lặp định kỳ (CLB ngoại khóa, …): mẫu + JSON recurrence_rule đơn giản (weekly/daily) hoặc rrule RFC string.
     */
    public function up(): void
    {
        Schema::create('dispatch_request_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('dispatch_package_id')->nullable()->constrained('dispatch_packages')->nullOnDelete();

            $table->boolean('is_active')->default(true);

            $table->enum('trip_type', ['door_to_door', 'point_to_point', 'business', 'cargo']);
            $table->string('origin')->nullable();
            $table->string('destination')->nullable();
            $table->unsignedSmallInteger('passenger_count')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('arrive_offset_minutes')->nullable();

            $table->json('recurrence_rule');
            $table->date('recurrence_end_date')->nullable();
            $table->time('recurrence_time')->default('08:00:00');
            $table->date('start_date')->nullable();
            $table->time('return_time')->nullable();
            $table->unsignedSmallInteger('repeat_count')->nullable();

            $table->json('wizard_snapshot')->nullable();

            $table->timestamps();

            $table->index(['is_active', 'requester_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_request_templates');
    }
};
