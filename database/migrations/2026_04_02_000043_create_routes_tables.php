<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['door_to_door'])->default('door_to_door');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('route_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained('routes')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->enum('status', ['draft', 'approved', 'archived'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->json('metadata')->nullable(); // e.g. notes, computed distance, etc.
            $table->timestamps();

            $table->unique(['route_id', 'version']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('route_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_version_id')->constrained('route_versions')->cascadeOnDelete();
            $table->unsignedInteger('stop_order');
            $table->string('name')->nullable();
            $table->string('address')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->time('planned_time')->nullable();
            $table->timestamps();

            $table->unique(['route_version_id', 'stop_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_stops');
        Schema::dropIfExists('route_versions');
        Schema::dropIfExists('routes');
    }
};

