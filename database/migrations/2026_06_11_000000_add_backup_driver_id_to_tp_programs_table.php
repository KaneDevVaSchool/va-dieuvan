<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tp_programs', function (Blueprint $table) {
            $table->foreignId('backup_driver_id')
                ->nullable()
                ->after('default_driver_id')
                ->constrained('drivers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tp_programs', function (Blueprint $table) {
            $table->dropForeign(['backup_driver_id']);
            $table->dropColumn('backup_driver_id');
        });
    }
};
