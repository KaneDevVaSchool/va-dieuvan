<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('passenger_fare_rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('package_code')->unique();
            $table->string('package_label');

            $table->decimal('seat_7', 14, 2)->nullable();
            $table->decimal('seat_15', 14, 2)->nullable();
            $table->decimal('seat_28', 14, 2)->nullable();
            $table->decimal('seat_33', 14, 2)->nullable();
            $table->decimal('seat_45', 14, 2)->nullable();
            $table->decimal('limo_9', 14, 2)->nullable();
            $table->decimal('limo_11', 14, 2)->nullable();
            $table->decimal('driver_self_support', 14, 2)->nullable();

            $table->timestamps();
        });

        Schema::create('cargo_fare_rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('route_code')->unique();
            $table->string('route_label');
            $table->decimal('distance_km', 8, 2)->nullable();

            $table->decimal('one_crate_50_40_50', 14, 2);
            $table->decimal('crates_2_to_5_50_40_50', 14, 2);
            $table->decimal('van_500kg', 14, 2);
            $table->decimal('van_1000kg', 14, 2);
            $table->decimal('van_2000kg', 14, 2);
            $table->decimal('loading_assist_per_point', 14, 2);
            $table->decimal('waiting_fee_per_hour', 14, 2);

            $table->timestamps();
        });

        Schema::create('pricing_notes', function (Blueprint $table) {
            $table->id();
            $table->string('category', 64);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('title')->nullable();
            $table->text('body');
            $table->timestamps();

            $table->index(['category', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_notes');
        Schema::dropIfExists('cargo_fare_rates');
        Schema::dropIfExists('passenger_fare_rates');
    }
};
