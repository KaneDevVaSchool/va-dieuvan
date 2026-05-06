<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_passengers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('phone', 20)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['trip_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_passengers');
    }
};
