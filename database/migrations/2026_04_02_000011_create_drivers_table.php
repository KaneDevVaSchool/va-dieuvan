<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('full_name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('national_id')->nullable();
            $table->string('license_class')->nullable();
            $table->date('license_expires_at')->nullable();
            $table->enum('employment_status', ['active', 'on_leave', 'terminated'])->default('active');
            $table->enum('availability_status', ['available', 'busy', 'offline'])->default('available');
            $table->unsignedBigInteger('odometer_km')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employment_status', 'availability_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};
