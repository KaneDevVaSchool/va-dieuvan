<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispatch_requests', function (Blueprint $table) {
            $table->foreignId('cloned_from_id')
                ->nullable()
                ->after('dispatch_request_template_id')
                ->constrained('dispatch_requests')
                ->nullOnDelete();
            $table->index('cloned_from_id');
        });
    }

    public function down(): void
    {
        Schema::table('dispatch_requests', function (Blueprint $table) {
            $table->dropForeign(['cloned_from_id']);
            $table->dropIndex(['cloned_from_id']);
            $table->dropColumn('cloned_from_id');
        });
    }
};
