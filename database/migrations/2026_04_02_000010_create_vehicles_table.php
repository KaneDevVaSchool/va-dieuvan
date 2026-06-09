<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('license_plate')->unique();
            $table->string('owner_name')->nullable();
            $table->text('frame_engine_number')->nullable();
            $table->string('type')->nullable();
            $table->unsignedSmallInteger('year_manufactured')->nullable();
            $table->date('purchased_at')->nullable();
            $table->unsignedSmallInteger('usage_expires_year')->nullable();
            $table->unsignedSmallInteger('seat_count')->nullable();
            $table->unsignedInteger('payload_kg')->nullable();
            $table->string('insurance_provider', 64)->nullable();
            $table->text('insurance_policy_note')->nullable();
            $table->enum('status', ['ready', 'in_use', 'maintenance', 'broken'])->default('ready');
            $table->unsignedBigInteger('odometer_km')->default(0);
            $table->unsignedBigInteger('default_driver_id')->nullable();
            $table->date('inspection_expires_at')->nullable();
            $table->date('insurance_expires_at')->nullable();
            $table->date('road_fee_expires_at')->nullable();
            $table->string('registration_cycle_note')->nullable();
            $table->date('last_maintenance_at')->nullable();
            $table->text('maintenance_schedule_note')->nullable();
            $table->string('caretaker_name')->nullable();
            $table->string('caretaker_phone', 32)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
