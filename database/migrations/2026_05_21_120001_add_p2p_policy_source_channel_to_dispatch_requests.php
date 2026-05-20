<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE dispatch_requests MODIFY source_channel VARCHAR(32) NOT NULL DEFAULT 'portal'");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE dispatch_requests MODIFY source_channel ENUM('portal','zalo','paper') NOT NULL DEFAULT 'portal'");
    }
};
