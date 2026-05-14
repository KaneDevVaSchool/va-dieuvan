<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispatch_requests', function (Blueprint $table) {
            $table->decimal('service_price', 12, 2)->nullable()->after('notes');
            $table->foreignId('price_filled_by')->nullable()->after('service_price')->constrained('users')->nullOnDelete();
            $table->timestamp('price_filled_at')->nullable()->after('price_filled_by');
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE dispatch_requests MODIFY COLUMN status ENUM('draft','pending','price_filled','approved','rejected','cancelled') NOT NULL DEFAULT 'draft'");
        }

        // SQLite: Laravel enum columns compile to VARCHAR + legacy CHECK constraint — replace column so `price_filled` is allowed (tests use sqlite :memory:).
        if ($driver === 'sqlite') {
            Schema::disableForeignKeyConstraints();

            Schema::table('dispatch_requests', function (Blueprint $table) {
                $table->dropIndex(['status', 'depart_at']);
            });

            DB::statement('ALTER TABLE dispatch_requests ADD COLUMN status_new VARCHAR(255) NOT NULL DEFAULT \'draft\'');
            DB::statement('UPDATE dispatch_requests SET status_new = status');
            DB::statement('ALTER TABLE dispatch_requests DROP COLUMN status');
            DB::statement('ALTER TABLE dispatch_requests RENAME COLUMN status_new TO status');

            Schema::table('dispatch_requests', function (Blueprint $table) {
                $table->index(['status', 'depart_at']);
            });

            Schema::enableForeignKeyConstraints();
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE dispatch_requests MODIFY COLUMN status ENUM('draft','pending','approved','rejected','cancelled') NOT NULL DEFAULT 'draft'");
        }

        Schema::table('dispatch_requests', function (Blueprint $table) {
            $table->dropForeign(['price_filled_by']);
            $table->dropColumn(['service_price', 'price_filled_by', 'price_filled_at']);
        });
    }
};
