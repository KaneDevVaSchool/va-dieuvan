<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispatch_requests', function (Blueprint $table) {
            $table->string('source_channel', 32)->default('portal')->after('approved_by');
            $table->boolean('is_urgent')->default(false)->after('source_channel');

            // paper proposal flow: nhận phiếu giấy / ký số (tương lai)
            $table->enum('paper_status', ['pending', 'received', 'digitally_signed'])->default('pending')->after('status');
            $table->dateTime('paper_received_at')->nullable()->after('paper_status');
            $table->string('paper_reference')->nullable()->after('paper_received_at'); // số phiếu / mã lưu trữ
        });
    }

    public function down(): void
    {
        Schema::table('dispatch_requests', function (Blueprint $table) {
            $table->dropColumn(['source_channel', 'is_urgent', 'paper_status', 'paper_received_at', 'paper_reference']);
        });
    }
};

