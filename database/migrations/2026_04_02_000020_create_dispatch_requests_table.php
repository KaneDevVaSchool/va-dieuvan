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

            $table->enum('trip_type', ['door_to_door', 'point_to_point', 'business', 'cargo']);
            $table->string('origin')->nullable();
            $table->string('destination')->nullable();
            $table->dateTime('depart_at');
            $table->dateTime('arrive_by')->nullable();

            $table->unsignedSmallInteger('passenger_count')->nullable();
            $table->text('notes')->nullable();

            $table->enum('status', ['draft', 'pending', 'approved', 'rejected', 'cancelled'])->default('draft');
            $table->string('rejection_reason')->nullable();

            // BRD: cảnh báo trùng yêu cầu cùng người/cùng giờ -> app validation + index hỗ trợ query
            $table->timestamps();

            $table->index(['requester_id', 'depart_at']);
            $table->index(['status', 'depart_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_requests');
    }
};

