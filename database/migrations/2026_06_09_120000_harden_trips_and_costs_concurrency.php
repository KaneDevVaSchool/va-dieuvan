<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dateTime('recurring_package_session_consumed_at')->nullable()->after('paid_at');
            $table->unique('dispatch_request_id', 'trips_dispatch_request_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropUnique('trips_dispatch_request_id_unique');
            $table->dropColumn('recurring_package_session_consumed_at');
        });
    }
};
