<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nguồn tạo user: cms (lệnh cms:sync-users), manual (admin thêm tay — người ngoài CMS),
 * google (tự tạo khi đăng nhập Google lần đầu). NULL = dữ liệu cũ, chưa rõ nguồn.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('source', 16)->nullable()->after('is_active');
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['source']);
            $table->dropColumn('source');
        });
    }
};
