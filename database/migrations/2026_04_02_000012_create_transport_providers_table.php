<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transport_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['vendor', 'taxi'])->default('vendor');
            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_providers');
    }
};

