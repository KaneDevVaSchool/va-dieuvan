<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_compliance_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('drivers')->cascadeOnDelete();
            $table->string('doc_type', 64);
            $table->string('title')->nullable();
            $table->text('notes')->nullable();
            $table->date('issued_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->timestamps();

            $table->index(['driver_id', 'doc_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_compliance_documents');
    }
};
