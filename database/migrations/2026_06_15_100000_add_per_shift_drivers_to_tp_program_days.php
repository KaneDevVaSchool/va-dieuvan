<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tp_program_days', function (Blueprint $table) {
            $table->foreignId('morning_driver_id')->nullable()->after('backup_assigned_by')->constrained('drivers')->nullOnDelete();
            $table->foreignId('morning_backup_driver_id')->nullable()->after('morning_driver_id')->constrained('drivers')->nullOnDelete();
            $table->foreignId('afternoon_driver_id')->nullable()->after('morning_backup_driver_id')->constrained('drivers')->nullOnDelete();
            $table->foreignId('afternoon_backup_driver_id')->nullable()->after('afternoon_driver_id')->constrained('drivers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tp_program_days', function (Blueprint $table) {
            $table->dropForeign(['morning_driver_id']);
            $table->dropForeign(['morning_backup_driver_id']);
            $table->dropForeign(['afternoon_driver_id']);
            $table->dropForeign(['afternoon_backup_driver_id']);
            $table->dropColumn([
                'morning_driver_id',
                'morning_backup_driver_id',
                'afternoon_driver_id',
                'afternoon_backup_driver_id',
            ]);
        });
    }
};
