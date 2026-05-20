<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispatch_requests', function (Blueprint $table) {
            $table->unsignedSmallInteger('student_count_actual')->nullable()->after('passenger_count');
            $table->timestamp('locked_at')->nullable()->after('student_count_actual');
        });
    }

    public function down(): void
    {
        Schema::table('dispatch_requests', function (Blueprint $table) {
            $table->dropColumn(['student_count_actual', 'locked_at']);
        });
    }
};
