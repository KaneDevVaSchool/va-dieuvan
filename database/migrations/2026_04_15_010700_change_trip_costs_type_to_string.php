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
            DB::statement("ALTER TABLE trip_costs MODIFY COLUMN type VARCHAR(64) NOT NULL DEFAULT 'other'");

            return;
        }

        if ($driver === 'sqlite') {
            Schema::disableForeignKeyConstraints();
            Schema::dropIfExists('trip_costs');
            Schema::create('trip_costs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('trip_id')->constrained('trips')->cascadeOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();

                $table->string('type', 64)->default('other');
                $table->decimal('amount', 14, 2)->default(0);
                $table->string('currency', 3)->default('VND');
                $table->string('description')->nullable();
                $table->string('receipt_url')->nullable();

                $table->string('status')->default('draft');
                $table->string('rejection_reason')->nullable();
                $table->dateTime('confirmed_at')->nullable();

                $table->timestamps();

                $table->index(['trip_id', 'type']);
                $table->index(['status', 'created_at']);
            });
            Schema::enableForeignKeyConstraints();
        }
    }

    public function down(): void
    {
        // Không tự động thu hẹp cột / ENUM.
    }
};
