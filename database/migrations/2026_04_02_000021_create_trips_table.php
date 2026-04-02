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

            // internal assignment
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();

            // external assignment (NCC/taxi)
            $table->foreignId('transport_provider_id')->nullable()->constrained('transport_providers')->nullOnDelete();
            $table->string('external_vehicle_ref')->nullable();
            $table->string('external_driver_ref')->nullable();

            $table->enum('status', [
                'pending',       // created from approved request or direct by dispatcher
                'approved',
                'assigned',
                'driver_confirmed',
                'in_progress',
                'completed',
                'cancelled',
                'incident',
            ])->default('pending');

            $table->dateTime('depart_at');
            $table->dateTime('arrive_by')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();

            // optimistic locking for BR-002 (application checks version on update)
            $table->unsignedInteger('lock_version')->default(0);

            $table->timestamps();

            $table->index(['status', 'depart_at']);
            $table->index(['vehicle_id', 'depart_at']);
            $table->index(['driver_id', 'depart_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};

