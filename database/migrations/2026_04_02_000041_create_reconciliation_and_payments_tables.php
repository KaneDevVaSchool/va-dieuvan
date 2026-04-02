<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reconciliation_periods', function (Blueprint $table) {
            $table->id();
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['draft', 'locked', 'paid'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('locked_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['start_date', 'end_date']);
            $table->index(['status', 'start_date']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('trips')->cascadeOnDelete();
            $table->foreignId('reconciliation_period_id')->nullable()->constrained('reconciliation_periods')->nullOnDelete();
            $table->enum('status', ['pending', 'paid', 'failed', 'cancelled'])->default('pending');
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('currency', 3)->default('VND');
            $table->string('method')->nullable(); // bank_transfer, cash...
            $table->string('reference')->nullable(); // bank ref or voucher
            $table->foreignId('executed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('executed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['trip_id']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('reconciliation_periods');
    }
};

