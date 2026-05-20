<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispatch_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('dispatch_requests', 'student_count_actual')) {
                if (Schema::hasColumn('dispatch_requests', 'passenger_count')) {
                    $table->unsignedSmallInteger('student_count_actual')->nullable()->after('passenger_count');
                } else {
                    $table->unsignedSmallInteger('student_count_actual')->nullable();
                }
            }
            if (! Schema::hasColumn('dispatch_requests', 'locked_at')) {
                if (Schema::hasColumn('dispatch_requests', 'student_count_actual')) {
                    $table->timestamp('locked_at')->nullable()->after('student_count_actual');
                } else {
                    $table->timestamp('locked_at')->nullable();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('dispatch_requests', function (Blueprint $table) {
            if (Schema::hasColumn('dispatch_requests', 'locked_at')) {
                $table->dropColumn('locked_at');
            }
            if (Schema::hasColumn('dispatch_requests', 'student_count_actual')) {
                $table->dropColumn('student_count_actual');
            }
        });
    }
};
