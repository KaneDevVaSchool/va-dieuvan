<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispatch_packages', function (Blueprint $table) {
            $table->timestamp('last_budget_alert_notified_at')->nullable()->after('monthly_budget');
        });
    }

    public function down(): void
    {
        Schema::table('dispatch_packages', function (Blueprint $table) {
            $table->dropColumn('last_budget_alert_notified_at');
        });
    }
};
