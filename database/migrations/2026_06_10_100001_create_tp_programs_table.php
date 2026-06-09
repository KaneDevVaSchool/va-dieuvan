<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tp_programs', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->text('description')->nullable();

            $table->string('origin_name')->nullable();
            $table->string('destination_name')->nullable();
            $table->unsignedBigInteger('origin_location_id')->nullable();
            $table->unsignedBigInteger('destination_location_id')->nullable();

            $table->time('departure_time');
            $table->time('return_time')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->json('runs_on');
            $table->json('excluded_dates')->nullable();
            $table->json('extra_dates')->nullable();

            $table->foreignId('default_driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->foreignId('backup_driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->foreignId('default_vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();

            $table->decimal('cost_per_trip', 12, 2)->nullable();
            $table->string('cost_currency', 3)->default('VND');
            $table->text('cost_notes')->nullable();

            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['draft', 'active', 'paused', 'completed', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->json('settings')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tp_programs');
    }
};
