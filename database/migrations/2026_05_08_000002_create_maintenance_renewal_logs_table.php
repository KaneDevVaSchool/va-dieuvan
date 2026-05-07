<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_renewal_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_item_id')
                ->constrained('vehicle_maintenance_items')
                ->cascadeOnDelete();
            $table->string('action'); // renewed|updated|noted
            $table->unsignedBigInteger('amount_paid')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('maintenance_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_renewal_logs');
    }
};
