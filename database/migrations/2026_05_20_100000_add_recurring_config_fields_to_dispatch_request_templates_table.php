<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispatch_request_templates', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('recurrence_time');
            $table->time('return_time')->nullable()->after('start_date');
            $table->unsignedSmallInteger('repeat_count')->nullable()->after('recurrence_end_date');
        });
    }

    public function down(): void
    {
        Schema::table('dispatch_request_templates', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'return_time', 'repeat_count']);
        });
    }
};
