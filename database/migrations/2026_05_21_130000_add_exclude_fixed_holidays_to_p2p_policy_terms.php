<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('p2p_policy_terms', function (Blueprint $table) {
            $table->boolean('exclude_fixed_holidays')->default(true)->after('weekdays_mask');
        });
    }

    public function down(): void
    {
        Schema::table('p2p_policy_terms', function (Blueprint $table) {
            $table->dropColumn('exclude_fixed_holidays');
        });
    }
};
