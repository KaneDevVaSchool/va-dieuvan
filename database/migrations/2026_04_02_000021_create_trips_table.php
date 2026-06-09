<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispatch_request_id')->nullable()->constrained('dispatch_requests')->nullOnDelete();

            $table->foreignId('dispatcher_id')->nullable()->constrained('users')->nullOnDelete();

            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();

            $table->foreignId('transport_provider_id')->nullable()->constrained('transport_providers')->nullOnDelete();
            $table->string('external_vehicle_ref')->nullable();
            $table->string('external_driver_ref')->nullable();

            $table->enum('status', [
                'pending',
                'approved',
                'assigned',
                'driver_confirmed',
                'in_progress',
                'completed',
                'cancelled',
                'incident',
            ])->default('pending');

            $table->enum('payment_status', ['unpaid', 'pending', 'paid'])->default('unpaid');

            $table->dateTime('depart_at');
            $table->dateTime('arrive_by')->nullable();
            $table->dateTime('planned_end_at')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('paid_at')->nullable();

            $table->unsignedInteger('lock_version')->default(0);
            $table->json('passenger_check_ins')->nullable();
            $table->json('supplement_transports')->nullable();
            $table->json('schedule_assignments')->nullable();

            $table->timestamps();

            $table->index(['status', 'depart_at']);
            $table->index(['vehicle_id', 'depart_at']);
            $table->index(['driver_id', 'depart_at']);
            $table->index(['dispatcher_id', 'depart_at'], 'trips_dispatcher_depart_idx');
            $table->index(['transport_provider_id', 'depart_at'], 'trips_provider_depart_idx');
            $table->index(['status', 'payment_status', 'depart_at'], 'trips_status_pay_depart_idx');
            $table->index(['vehicle_id', 'depart_at', 'planned_end_at'], 'trips_vehicle_interval_idx');
            $table->index(['driver_id', 'depart_at', 'planned_end_at'], 'trips_driver_interval_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
