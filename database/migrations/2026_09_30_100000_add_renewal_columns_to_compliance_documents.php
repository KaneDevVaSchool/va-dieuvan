<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gia hạn chứng từ: bản mới thay thế bản cũ (không xóa) để giữ lịch sử.
 * - status: active | superseded
 * - replaces_id: bản cũ mà bản này thay thế
 */
return new class extends Migration
{
    private const TABLES = ['vehicle_compliance_documents', 'driver_compliance_documents'];

    public function up(): void
    {
        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->string('document_no', 100)->nullable()->after('title');
                $table->string('status', 16)->default('active')->after('expires_at');
                $table->foreignId('replaces_id')->nullable()->after('status')
                    ->constrained($tableName)->nullOnDelete();

                $table->index(['status', 'doc_type', 'expires_at']);
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropIndex(['status', 'doc_type', 'expires_at']);
                $table->dropConstrainedForeignId('replaces_id');
                $table->dropColumn(['document_no', 'status']);
            });
        }
    }
};
