<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('attachments')
            ->where('attachable_type', 'App\Models\VehicleMaintenanceItem')
            ->delete();

        Schema::dropIfExists('maintenance_renewal_logs');
        Schema::dropIfExists('maintenance_reminders');
        Schema::dropIfExists('vehicle_maintenance_items');
    }

    public function down(): void
    {
        // Tính năng đã gỡ; không tái tạo schema tại đây.
    }
};
