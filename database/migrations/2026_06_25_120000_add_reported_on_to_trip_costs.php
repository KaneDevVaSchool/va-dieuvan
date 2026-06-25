<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_costs', function (Blueprint $table) {
            // Ngày báo cáo do tài xế nhập cho chi phí không gắn chuyến (standalone).
            // Null = dùng created_at làm mốc thời gian. Với chi phí gắn chuyến thường để null.
            $table->date('reported_on')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('trip_costs', function (Blueprint $table) {
            $table->dropColumn('reported_on');
        });
    }
};
