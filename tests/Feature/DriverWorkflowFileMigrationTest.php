<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\ChecklistSubmissionItem;
use App\Models\ExpenseDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DriverWorkflowFileMigrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('compliance');
        Storage::fake('local');
    }

    private function legacy(string $type = ExpenseDocument::class): Attachment
    {
        $folder = $type === ExpenseDocument::class ? 'expenses' : 'checklists';

        return Attachment::create(['attachable_type' => $type, 'attachable_id' => 123,
            'disk' => 'local', 'path' => "attachments/{$folder}/123/old.pdf", 'original_name' => 'old.pdf',
            'size_bytes' => strlen('legacy-scan'), 'file_binary' => 'legacy-scan']);
    }

    public function test_dry_run_apply_verify_and_prune_are_separate_and_idempotent(): void
    {
        foreach ([ExpenseDocument::class, ChecklistSubmissionItem::class] as $type) {
            $this->legacy($type);
        }
        $this->artisan('driver-workflow:migrate-files')->assertSuccessful();
        $this->assertSame([], Storage::disk('compliance')->allFiles());
        $this->artisan('driver-workflow:migrate-files', ['--apply' => true, '--prune-binary' => true])->assertFailed();
        $this->assertSame(2, Attachment::whereNotNull('file_binary')->count());
        $this->artisan('driver-workflow:migrate-files', ['--apply' => true])->assertSuccessful();
        $this->artisan('driver-workflow:migrate-files', ['--apply' => true])->assertSuccessful();
        $this->assertCount(2, Storage::disk('compliance')->allFiles());
        $this->artisan('driver-workflow:migrate-files', ['--verify' => true])->assertSuccessful();
        $this->artisan('driver-workflow:migrate-files', ['--apply' => true, '--prune-binary' => true])->assertSuccessful();
        $this->assertSame(0, Attachment::whereNotNull('file_binary')->count());
        foreach (Attachment::all() as $file) {
            $this->assertSame('compliance', $file->disk);
            $this->assertSame(hash('sha256', 'legacy-scan'), $file->sha256);
            $this->assertSame('legacy-scan', Storage::disk('compliance')->get($file->path));
        }
        $this->artisan('driver-workflow:migrate-files', ['--verify' => true])->assertSuccessful();
    }

    public function test_disk_only_legacy_file_is_copied_without_deleting_source(): void
    {
        $old = $this->legacy(ChecklistSubmissionItem::class);
        Storage::disk('local')->put($old->path, 'legacy-scan');
        $old->update(['file_binary' => null]);
        $this->artisan('driver-workflow:migrate-files', ['--apply' => true])->assertSuccessful();
        $this->assertSame('legacy-scan', Storage::disk('local')->get($old->path));
        $this->assertSame('compliance', $old->fresh()->disk);
        $this->artisan('driver-workflow:migrate-files', ['--verify' => true])->assertSuccessful();
    }

    public function test_corrupted_target_does_not_prune_original_binary(): void
    {
        $old = $this->legacy();
        $this->artisan('driver-workflow:migrate-files', ['--apply' => true])->assertSuccessful();
        Storage::disk('compliance')->put($old->fresh()->path, 'broken');
        $this->artisan('driver-workflow:migrate-files', ['--verify' => true])->assertFailed();
        $this->artisan('driver-workflow:migrate-files', ['--apply' => true, '--prune-binary' => true])->assertFailed();
        $this->assertSame('legacy-scan', $old->fresh()->file_binary);
    }
}
