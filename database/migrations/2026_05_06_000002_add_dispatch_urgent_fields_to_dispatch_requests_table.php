<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispatch_requests', function (Blueprint $table) {
            $table->text('urgent_reason')->nullable()->after('is_urgent');
            $table->enum('urgent_trigger', ['auto', 'manual'])->nullable()->after('urgent_reason');
        });
    }

    public function down(): void
    {
        Schema::table('dispatch_requests', function (Blueprint $table) {
            $table->dropColumn(['urgent_reason', 'urgent_trigger']);
        });
    }
};
