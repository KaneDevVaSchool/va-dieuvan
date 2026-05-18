<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Biểu mẫu đã lưu của portal user — để tái sử dụng khi tạo yêu cầu mới.
     */
    public function up(): void
    {
        Schema::create('portal_form_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('name', 100);
            $table->enum('trip_type', ['door_to_door', 'point_to_point', 'business', 'cargo']);
            $table->json('wizard_snapshot')->nullable();

            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_form_templates');
    }
};
