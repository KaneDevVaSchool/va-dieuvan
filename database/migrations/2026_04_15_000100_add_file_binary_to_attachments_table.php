<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE attachments ADD COLUMN file_binary LONGBLOB NULL');
        } else {
            Schema::table('attachments', function (Blueprint $table) {
                $table->binary('file_binary')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('attachments', function (Blueprint $table) {
            if (Schema::hasColumn('attachments', 'file_binary')) {
                $table->dropColumn('file_binary');
            }
        });
    }
};
