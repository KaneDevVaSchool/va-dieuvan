<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('event'); // e.g. request.approve, trip.assign
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();

            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->json('metadata')->nullable(); // ip, user_agent, reason, etc.

            $table->timestamps();

            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['event', 'created_at']);
            $table->index(['actor_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};

