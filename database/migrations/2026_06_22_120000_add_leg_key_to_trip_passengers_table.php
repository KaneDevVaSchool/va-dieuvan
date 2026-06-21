<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_passengers', function (Blueprint $table) {
            $table->string('leg_key', 64)->nullable()->after('trip_id');
            $table->index(['trip_id', 'leg_key']);
        });
    }

    public function down(): void
    {
        Schema::table('trip_passengers', function (Blueprint $table) {
            $table->dropIndex(['trip_id', 'leg_key']);
            $table->dropColumn('leg_key');
        });
    }
};
