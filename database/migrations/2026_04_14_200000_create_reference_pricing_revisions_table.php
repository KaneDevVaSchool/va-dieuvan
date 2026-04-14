<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reference_pricing_revisions', function (Blueprint $table) {
            $table->id();
            // Không dùng morphs(): tên index mặc định vượt giới hạn 64 ký tự của MySQL.
            $table->string('revisionable_type');
            $table->unsignedBigInteger('revisionable_id');
            $table->index(['revisionable_type', 'revisionable_id'], 'rp_rev_morph_idx');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('snapshot');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reference_pricing_revisions');
    }
};
