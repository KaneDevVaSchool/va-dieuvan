<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cargo_shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispatch_request_id')->nullable()->constrained('dispatch_requests')->nullOnDelete();
            $table->foreignId('trip_id')->nullable()->constrained('trips')->nullOnDelete();

            $table->string('tracking_code')->nullable()->unique();
            $table->string('sender_name')->nullable();
            $table->string('receiver_name')->nullable();
            $table->string('pickup_address')->nullable();
            $table->string('delivery_address')->nullable();
            $table->unsignedInteger('weight_grams')->nullable();
            $table->unsignedInteger('quantity')->nullable();

            $table->dateTime('sla_due_at')->nullable(); // SLA <= 3h
            $table->enum('status', ['pending', 'picked_up', 'in_transit', 'delivered', 'failed', 'cancelled'])
                ->default('pending');

            $table->dateTime('picked_up_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'sla_due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cargo_shipments');
    }
};

