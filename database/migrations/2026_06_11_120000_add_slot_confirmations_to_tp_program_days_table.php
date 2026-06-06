<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Thêm xác nhận theo ca (sáng / chiều) cho từng ngày vận hành.
 * Khi chương trình chỉ có 1 ca, dùng confirmed_at cũ (backward-compat).
 * Khi có 2 ca, mỗi ca xác nhận độc lập qua morning_confirmed_at / afternoon_confirmed_at.
 *
 * (Chạy sau 2026_06_11_000001 — cần cột confirmed_by_driver_id.)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('tp_program_days', 'morning_confirmed_at')) {
            return;
        }

        Schema::table('tp_program_days', function (Blueprint $table) {
            $table->timestamp('morning_confirmed_at')->nullable()->after('confirmed_by_driver_id');
            $table->foreignId('morning_confirmed_by_driver_id')->nullable()
                ->after('morning_confirmed_at')
                ->constrained('drivers')->nullOnDelete();

            $table->timestamp('afternoon_confirmed_at')->nullable()->after('morning_confirmed_by_driver_id');
            $table->foreignId('afternoon_confirmed_by_driver_id')->nullable()
                ->after('afternoon_confirmed_at')
                ->constrained('drivers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('tp_program_days', 'morning_confirmed_at')) {
            return;
        }

        Schema::table('tp_program_days', function (Blueprint $table) {
            $table->dropConstrainedForeignId('morning_confirmed_by_driver_id');
            $table->dropColumn('morning_confirmed_at');
            $table->dropConstrainedForeignId('afternoon_confirmed_by_driver_id');
            $table->dropColumn('afternoon_confirmed_at');
        });
    }
};
