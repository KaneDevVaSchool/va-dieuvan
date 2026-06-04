<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tp_students', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('full_name');
            $table->string('grade', 20)->nullable();
            $table->string('class_name', 50)->nullable();
            $table->unsignedBigInteger('campus_id')->nullable();
            $table->string('parent_name')->nullable();
            $table->string('parent_phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->enum('status', ['active', 'inactive', 'transferred', 'graduated'])->default('active');
            $table->json('metadata')->nullable();
            $table->enum('source', ['manual', 'excel_import', 'api_sync'])->default('manual');
            $table->string('external_id', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'grade']);
            $table->index('class_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tp_students');
    }
};
