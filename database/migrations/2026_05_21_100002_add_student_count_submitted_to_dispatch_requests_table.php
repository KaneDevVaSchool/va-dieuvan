<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('dispatch_requests', 'student_count_submitted_at')
            && Schema::hasColumn('dispatch_requests', 'student_count_submitted_by')) {
            return;
        }

        Schema::table('dispatch_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('dispatch_requests', 'student_count_submitted_at')) {
                if (Schema::hasColumn('dispatch_requests', 'locked_at')) {
                    $table->timestamp('student_count_submitted_at')->nullable()->after('locked_at');
                } else {
                    $table->timestamp('student_count_submitted_at')->nullable();
                }
            }
            if (! Schema::hasColumn('dispatch_requests', 'student_count_submitted_by')) {
                $table->foreignId('student_count_submitted_by')
                    ->nullable()
                    ->after('student_count_submitted_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('dispatch_requests', function (Blueprint $table) {
            if (Schema::hasColumn('dispatch_requests', 'student_count_submitted_by')) {
                $table->dropConstrainedForeignId('student_count_submitted_by');
            }
            if (Schema::hasColumn('dispatch_requests', 'student_count_submitted_at')) {
                $table->dropColumn('student_count_submitted_at');
            }
        });
    }
};
