<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_costs', function (Blueprint $table) {
            $table->foreignId('vehicle_id')
                ->nullable()
                ->after('trip_id')
                ->constrained('vehicles')
                ->nullOnDelete();
            $table->index(['vehicle_id', 'created_at'], 'trip_costs_vehicle_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('trip_costs', function (Blueprint $table) {
            $table->dropIndex('trip_costs_vehicle_created_idx');
            $table->dropConstrainedForeignId('vehicle_id');
        });
    }
};
