<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispatch_settings', function (Blueprint $table) {
            $table->string('reference_pricing_url', 2048)->nullable()->after('cargo_urgent_threshold_hours');
        });
    }

    public function down(): void
    {
        Schema::table('dispatch_settings', function (Blueprint $table) {
            $table->dropColumn('reference_pricing_url');
        });
    }
};
