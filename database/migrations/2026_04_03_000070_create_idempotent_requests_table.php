<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idempotent_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('scope', 512);
            $table->string('key_hash', 64);
            $table->unsignedSmallInteger('status_code')->default(0);
            $table->longText('response_body')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'scope', 'key_hash'], 'idempotent_user_scope_key_uq');
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idempotent_requests');
    }
};
