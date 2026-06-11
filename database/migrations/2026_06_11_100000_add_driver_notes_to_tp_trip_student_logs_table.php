<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tp_trip_student_logs', function (Blueprint $table) {
            $table->text('driver_notes')->nullable()->after('absence_notes');
        });
    }

    public function down(): void
    {
        Schema::table('tp_trip_student_logs', function (Blueprint $table) {
            $table->dropColumn('driver_notes');
        });
    }
};
