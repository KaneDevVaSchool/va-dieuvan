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
            $table->string('type')->nullable(); // e.g. 16-seat, 7-seat, van
            $table->unsignedSmallInteger('seat_count')->nullable();
            $table->unsignedInteger('payload_kg')->nullable();
            $table->enum('status', ['ready', 'in_use', 'maintenance', 'broken'])->default('ready');
            $table->unsignedBigInteger('odometer_km')->default(0);
            $table->date('inspection_expires_at')->nullable();
            $table->date('insurance_expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};

