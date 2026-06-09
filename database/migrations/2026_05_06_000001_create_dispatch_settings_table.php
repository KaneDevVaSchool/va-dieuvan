<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatch_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('passenger_urgent_threshold_hours')->default(72);
            $table->unsignedInteger('cargo_urgent_threshold_hours')->default(24);
            $table->string('reference_pricing_url', 2048)->nullable();
            $table->timestamps();
        });

        DB::table('dispatch_settings')->insert([
            'passenger_urgent_threshold_hours' => 72,
            'cargo_urgent_threshold_hours' => 24,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_settings');
    }
};
