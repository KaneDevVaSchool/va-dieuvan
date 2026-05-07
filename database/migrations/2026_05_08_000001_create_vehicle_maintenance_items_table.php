<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_maintenance_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->string('type', 64); // registration|insurance_mandatory|insurance_hull|oil|tire|air_filter|brake|custom
            $table->string('name');
            $table->date('expiry_date')->nullable();
            $table->date('next_service_date')->nullable();
            $table->date('last_service_date')->nullable();
            $table->unsignedInteger('next_service_km')->nullable();
            $table->unsignedInteger('last_service_km')->nullable();
            $table->string('issued_by')->nullable();
            $table->unsignedBigInteger('estimated_renewal_cost')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('reminder_enabled')->default(false);
            $table->unsignedTinyInteger('reminder_days_before')->nullable();
            $table->foreignId('last_updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['vehicle_id', 'type']);
            $table->index(['vehicle_id', 'expiry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_maintenance_items');
    }
};
