<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispatch_requests', function (Blueprint $table) {
            $table->foreignId('dispatch_request_template_id')
                ->nullable()
                ->after('requester_id')
                ->constrained('dispatch_request_templates')
                ->nullOnDelete();
            $table->index(['dispatch_request_template_id', 'depart_at'], 'dispatch_requests_template_depart_idx');
        });
    }

    public function down(): void
    {
        Schema::table('dispatch_requests', function (Blueprint $table) {
            $table->dropIndex('dispatch_requests_template_depart_idx');
            $table->dropConstrainedForeignId('dispatch_request_template_id');
        });
    }
};
