<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tp_trip_executions', function (Blueprint $table) {
            $table->dropUnique(['program_day_id']);
            $table->string('shift', 16)->default('morning')->after('program_day_id');
            $table->unique(['program_day_id', 'shift']);
        });

    }

    public function down(): void
    {
        Schema::table('tp_trip_executions', function (Blueprint $table) {
            $table->dropUnique(['program_day_id', 'shift']);
            $table->dropColumn('shift');
            $table->unique('program_day_id');
        });
    }
};
