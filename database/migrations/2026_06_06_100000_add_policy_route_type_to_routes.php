<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ENUM MODIFY chỉ áp dụng cho MySQL. SQLite (test) lưu enum dạng varchar+CHECK;
        // bỏ qua để migration chạy cross-DB (không phá test suite).
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE routes MODIFY COLUMN type ENUM('door_to_door', 'policy') NOT NULL DEFAULT 'door_to_door'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("UPDATE routes SET type = 'door_to_door' WHERE type = 'policy'");
        DB::statement("ALTER TABLE routes MODIFY COLUMN type ENUM('door_to_door') NOT NULL DEFAULT 'door_to_door'");
    }
};
