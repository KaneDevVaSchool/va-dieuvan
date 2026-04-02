<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('trips')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->unsignedBigInteger('start_odometer_km')->nullable();
            $table->unsignedBigInteger('end_odometer_km')->nullable();
            $table->unsignedBigInteger('distance_km')->nullable();

            $table->text('driver_notes')->nullable();
            $table->text('dispatcher_notes')->nullable();

            $table->timestamps();
        });

        Schema::create('trip_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('trips')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('type', ['fuel', 'toll', 'parking', 'other'])->default('other');
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('currency', 3)->default('VND');
            $table->string('description')->nullable();
            $table->string('receipt_url')->nullable();

            $table->enum('status', ['draft', 'submitted', 'confirmed', 'rejected'])->default('draft');
            $table->string('rejection_reason')->nullable();
            $table->dateTime('confirmed_at')->nullable();

            $table->timestamps();

            $table->index(['trip_id', 'type']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_costs');
        Schema::dropIfExists('trip_records');
    }
};

