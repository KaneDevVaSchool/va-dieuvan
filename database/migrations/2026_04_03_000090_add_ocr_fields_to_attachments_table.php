<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attachments', function (Blueprint $table) {
            $table->text('ocr_text')->nullable()->after('mime_type');
            $table->json('ocr_meta')->nullable()->after('ocr_text');
            $table->timestamp('ocr_processed_at')->nullable()->after('ocr_meta');
        });
    }

    public function down(): void
    {
        Schema::table('attachments', function (Blueprint $table) {
            $table->dropColumn(['ocr_text', 'ocr_meta', 'ocr_processed_at']);
        });
    }
};
