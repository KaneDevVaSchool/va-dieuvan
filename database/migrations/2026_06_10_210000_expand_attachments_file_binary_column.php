<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('attachments')) {
            return;
        }

        // Cú pháp MODIFY ... LONGBLOB chỉ áp dụng cho MySQL. SQLite/Postgres dùng cho test
        // không cần (cột binary() đã chứa được blob lớn).
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Laravel binary() => MySQL BLOB (~64KB). Uploads allow up to 10MB.
        DB::statement('ALTER TABLE `attachments` MODIFY `file_binary` LONGBLOB NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('attachments')) {
            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE `attachments` MODIFY `file_binary` BLOB NULL');
    }
};
