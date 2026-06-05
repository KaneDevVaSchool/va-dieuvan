<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tp_program_days', function (Blueprint $table) {
            $table->timestamp('confirmed_at')->nullable()->after('assigned_by');
            $table->foreignId('confirmed_by_driver_id')->nullable()->after('confirmed_at')
                ->constrained('drivers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tp_program_days', function (Blueprint $table) {
            $table->dropConstrainedForeignId('confirmed_by_driver_id');
            $table->dropColumn('confirmed_at');
        });
    }
};
