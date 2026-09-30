<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite (dùng cho test) không hỗ trợ dropForeign — bỏ qua, chỉ áp dụng cho MySQL.
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('tp_trip_executions', function (Blueprint $table) {
            $table->dropForeign(['driver_id']);
            $table->unsignedBigInteger('driver_id')->nullable()->change();
            $table->foreign('driver_id')->references('id')->on('drivers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('tp_trip_executions', function (Blueprint $table) {
            $table->dropForeign(['driver_id']);
            $table->unsignedBigInteger('driver_id')->nullable(false)->change();
            $table->foreign('driver_id')->references('id')->on('drivers')->restrictOnDelete();
        });
    }
};
