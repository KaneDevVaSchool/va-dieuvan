<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('checklist_template_items')->where('requires_photo_on_ng', true)
            ->update(['requires_photo_on_ng' => false]);

        DB::table('checklist_submission_items')->where('requires_photo_on_ng', true)
            ->whereIn('submission_id', DB::table('checklist_submissions')->where('status', 'draft')->select('id'))
            ->update(['requires_photo_on_ng' => false]);
    }

    public function down(): void
    {
        // Previous per-item settings cannot be reconstructed. Evidence and notes are retained.
    }
};
