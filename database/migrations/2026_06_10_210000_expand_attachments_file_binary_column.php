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

        // Laravel binary() => MySQL BLOB (~64KB). Uploads allow up to 10MB.
        DB::statement('ALTER TABLE `attachments` MODIFY `file_binary` LONGBLOB NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('attachments')) {
            return;
        }

        DB::statement('ALTER TABLE `attachments` MODIFY `file_binary` BLOB NULL');
    }
};
