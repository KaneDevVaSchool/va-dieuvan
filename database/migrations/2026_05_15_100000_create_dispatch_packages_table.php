<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatch_packages', function (Blueprint $table) {
            $table->id();
            $table->enum('trip_type', ['door_to_door', 'point_to_point', 'business', 'cargo']);
            $table->string('label')->nullable();
            $table->unsignedSmallInteger('total_sessions');
            $table->unsignedSmallInteger('sessions_used')->default(0);
            /** Khi số buổi còn lại không vượt quá alert_when_remaining_sessions thì gửi cảnh báo (không chặn nghiệp vụ). */
            $table->unsignedSmallInteger('alert_when_remaining_sessions')->default(3);
            $table->timestamp('last_low_sessions_notified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_packages');
    }
};
