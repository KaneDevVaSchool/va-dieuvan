<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained('trips')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type');
            $table->text('message')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();

            $table->index(['trip_id', 'created_at']);
            $table->index(['type', 'created_at']);
            $table->index(['trip_id', 'type', 'created_at'], 'trip_events_trip_type_created_idx');
            $table->index(['created_by', 'created_at'], 'trip_events_creator_created_idx');
        });

        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('attachable_type');
            $table->unsignedBigInteger('attachable_id');
            $table->string('kind')->nullable();
            $table->string('disk')->default('public');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('mime_type')->nullable();
            $table->text('ocr_text')->nullable();
            $table->json('ocr_meta')->nullable();
            $table->timestamp('ocr_processed_at')->nullable();
            $table->binary('file_binary')->nullable();
            $table->string('sha256', 64)->nullable();
            $table->unsignedBigInteger('signed_document_version_id')->nullable();
            $table->timestamps();

            $table->index(['attachable_type', 'attachable_id']);
            $table->index(['kind', 'created_at']);
            $table->index(['uploaded_by', 'created_at'], 'attachments_uploader_created_idx');
            $table->index(['attachable_type', 'attachable_id', 'kind'], 'attachments_attachable_kind_idx');
        });

        DB::statement('ALTER TABLE `attachments` MODIFY `file_binary` LONGBLOB NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('trip_events');
    }
};
