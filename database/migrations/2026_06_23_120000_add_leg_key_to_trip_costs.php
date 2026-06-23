<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_costs', function (Blueprint $table) {
            // Chặng (lịch trình) mà khoản chi phí thuộc về; null = chi phí toàn chuyến.
            // Khóa chặng dạng "passenger:0" / "business:1" / "cargo:0" (khớp schedule_legs).
            $table->string('leg_key', 64)->nullable()->after('trip_id');
            $table->index(['trip_id', 'leg_key'], 'trip_costs_trip_leg_idx');
        });
    }

    public function down(): void
    {
        Schema::table('trip_costs', function (Blueprint $table) {
            $table->dropIndex('trip_costs_trip_leg_idx');
            $table->dropColumn('leg_key');
        });
    }
};
