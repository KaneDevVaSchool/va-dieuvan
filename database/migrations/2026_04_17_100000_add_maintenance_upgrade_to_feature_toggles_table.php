<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feature_toggles', function (Blueprint $table) {
            $table->boolean('maintenance_mode')->default(false)->after('is_enabled');
            $table->boolean('upgrade_notice')->default(false)->after('maintenance_mode');
        });
    }

    public function down(): void
    {
        Schema::table('feature_toggles', function (Blueprint $table) {
            $table->dropColumn(['maintenance_mode', 'upgrade_notice']);
        });
    }
};
