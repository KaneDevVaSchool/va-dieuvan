<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispatch_requests', function (Blueprint $table) {
            $table->timestamp('student_count_submitted_at')->nullable()->after('locked_at');
            $table->foreignId('student_count_submitted_by')->nullable()->after('student_count_submitted_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('dispatch_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('student_count_submitted_by');
            $table->dropColumn('student_count_submitted_at');
        });
    }
};
