<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sổ chi phí theo xe: bổ sung thông tin hóa đơn / người bán / số lượng để lập Bảng kê không hóa đơn
     * và Đề nghị thanh toán từ chính các dòng trip_costs.
     */
    public function up(): void
    {
        Schema::table('trip_costs', function (Blueprint $table) {
            $table->boolean('has_invoice')->default(true)->after('description');
            $table->string('invoice_no', 64)->nullable()->after('has_invoice');
            $table->string('seller_name')->nullable()->after('invoice_no');
            $table->string('seller_address')->nullable()->after('seller_name');
            $table->string('unit', 32)->nullable()->after('seller_address');
            $table->decimal('quantity', 12, 2)->nullable()->after('unit');
            $table->decimal('unit_price', 14, 2)->nullable()->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('trip_costs', function (Blueprint $table) {
            $table->dropColumn(['has_invoice', 'invoice_no', 'seller_name', 'seller_address', 'unit', 'quantity', 'unit_price']);
        });
    }
};
