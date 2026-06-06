<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tp_program_days', function (Blueprint $table) {
            $table->foreignId('backup_driver_id')->nullable()->after('driver_id')->constrained('drivers')->nullOnDelete();
            $table->timestamp('backup_assigned_at')->nullable()->after('assigned_by');
            $table->foreignId('backup_assigned_by')->nullable()->after('backup_assigned_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tp_program_days', function (Blueprint $table) {
            $table->dropForeign(['backup_driver_id']);
            $table->dropForeign(['backup_assigned_by']);
            $table->dropColumn(['backup_driver_id', 'backup_assigned_at', 'backup_assigned_by']);
        });
    }
};
