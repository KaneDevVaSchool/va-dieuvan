<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\VehicleComplianceDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ComplianceFileMigrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('compliance');
        Storage::fake('local');
        Storage::fake('public');
    }

    private function legacy(array $overrides = []): Attachment
    {
        return Attachment::create(array_merge([
            'attachable_type' => VehicleComplianceDocument::class,
            'attachable_id' => 123,
            'disk' => 'public',
            'path' => 'attachments/vehicle_compliance_documents/123/old.pdf',
            'original_name' => 'old.pdf',
            'size_bytes' => strlen('scan-content'),
            'sha256' => hash('sha256', 'scan-content'),
            'file_binary' => 'scan-content',
        ], $overrides))->fresh();
    }

    public function test_dry_run_does_not_write_files_or_modify_database(): void
    {
        $old = $this->legacy();
        $this->artisan('compliance:migrate-files')->assertSuccessful();
        $this->assertSame($old->getAttributes(), $old->fresh()->getAttributes());
        $this->assertSame([], Storage::disk('compliance')->allFiles());
    }

    public function test_apply_exports_binary_and_can_be_repeated_before_pruning(): void
    {
        $old = $this->legacy();
        $this->artisan('compliance:migrate-files', ['--apply' => true])->assertSuccessful();
        $new = $old->fresh();
        $this->assertSame('compliance', $new->disk);
        $this->assertSame('scan-content', Storage::disk('compliance')->get($new->path));
        $this->assertSame('scan-content', $new->file_binary);
        $this->artisan('compliance:migrate-files', ['--apply' => true])->assertSuccessful();
        $this->assertCount(1, Storage::disk('compliance')->allFiles());
        $this->artisan('compliance:migrate-files', ['--verify' => true])->assertSuccessful();
        $this->artisan('compliance:migrate-files', ['--apply' => true, '--prune-binary' => true])->assertSuccessful();
        $this->assertNull($old->fresh()->file_binary);
        $this->artisan('compliance:migrate-files', ['--apply' => true, '--prune-binary' => true])->assertSuccessful();
        $this->artisan('compliance:migrate-files', ['--verify' => true])->assertSuccessful();
    }

    public function test_prune_requires_explicit_apply_and_previously_verified_destination(): void
    {
        $old = $this->legacy();
        $this->artisan('compliance:migrate-files', ['--prune-binary' => true])->assertExitCode(2);
        $this->artisan('compliance:migrate-files', ['--apply' => true, '--prune-binary' => true])->assertFailed();
        $this->assertSame('scan-content', $old->fresh()->file_binary);
    }

    public function test_missing_or_corrupt_target_never_causes_binary_to_be_pruned(): void
    {
        $old = $this->legacy();
        $this->artisan('compliance:migrate-files', ['--apply' => true])->assertSuccessful();
        $path = $old->fresh()->path;
        // Cùng kích thước để chắc chắn SHA-256 được kiểm tra.
        Storage::disk('compliance')->put($path, 'bad!-content');
        $this->artisan('compliance:migrate-files', ['--verify' => true])->assertFailed();
        $this->artisan('compliance:migrate-files', ['--apply' => true, '--prune-binary' => true])->assertFailed();
        $this->assertSame('scan-content', $old->fresh()->file_binary);
        Storage::disk('compliance')->delete($path);
        $this->artisan('compliance:migrate-files', ['--apply' => true, '--prune-binary' => true])->assertFailed();
        $this->assertSame('scan-content', $old->fresh()->file_binary);
    }

    public function test_invalid_sources_are_reported_without_modification_and_other_rows_continue(): void
    {
        $bad = $this->legacy(['file_binary' => 'corrupted']);
        $good = $this->legacy(['path' => 'attachments/vehicle_compliance_documents/123/second.pdf']);
        $this->artisan('compliance:migrate-files', ['--apply' => true])->assertFailed();
        $this->assertSame('public', $bad->fresh()->disk);
        $this->assertSame('corrupted', $bad->fresh()->file_binary);
        $this->assertSame('compliance', $good->fresh()->disk);
    }

    public function test_legacy_mislabeled_disk_and_invalid_binary_fall_back_to_verified_disk_file(): void
    {
        $old = $this->legacy(['file_binary' => 'bad!-content']);
        Storage::disk('local')->put($old->path, 'scan-content');
        $this->artisan('compliance:migrate-files', ['--apply' => true])->assertSuccessful();
        $this->assertSame('scan-content', Storage::disk('compliance')->get($old->fresh()->path));
        $this->assertSame('scan-content', Storage::disk('local')->get($old->path), 'Nguồn cũ được giữ nguyên');
        $this->artisan('compliance:migrate-files', ['--apply' => true, '--prune-binary' => true])->assertSuccessful();
        $this->assertNull($old->fresh()->file_binary);
    }

    public function test_disk_only_legacy_alias_is_migrated_and_other_attachment_types_are_untouched(): void
    {
        $old = $this->legacy(['attachable_type' => 'driver_compliance_document',
            'path' => 'storage/attachments/driver_compliance_documents/123/license.jpg', 'file_binary' => null]);
        Storage::disk('local')->put('attachments/driver_compliance_documents/123/license.jpg', 'scan-content');
        $unrelated = $this->legacy(['attachable_type' => 'trip']);
        $this->artisan('compliance:migrate-files', ['--apply' => true])->assertSuccessful();
        $new = $old->fresh();
        $this->assertSame('compliance', $new->disk);
        $this->assertStringStartsWith('attachments/driver_compliance_documents/', $new->path);
        $this->assertNull($new->file_binary);
        $this->assertSame($unrelated->getAttributes(), $unrelated->fresh()->getAttributes());
    }

    public function test_existing_conflicting_target_is_never_overwritten(): void
    {
        $old = $this->legacy();
        $path = "attachments/vehicle_compliance_documents/123/migrated-{$old->id}-{$old->sha256}";
        Storage::disk('compliance')->put($path, 'do-not-overwrite');
        $this->artisan('compliance:migrate-files', ['--apply' => true])->assertFailed();
        $this->assertSame('do-not-overwrite', Storage::disk('compliance')->get($path));
        $this->assertSame($old->getAttributes(), $old->fresh()->getAttributes());
    }

    public function test_storage_write_failure_leaves_binary_and_metadata_unchanged(): void
    {
        $old = $this->legacy();
        $disk = \Mockery::mock(\Illuminate\Filesystem\FilesystemAdapter::class);
        $disk->shouldReceive('exists')->andReturn(false);
        $disk->shouldReceive('put')->once()
            ->with(\Mockery::type('string'), 'scan-content', ['visibility' => 'private'])->andReturn(false);
        Storage::shouldReceive('disk')->with('compliance')->andReturn($disk);
        $this->artisan('compliance:migrate-files', ['--apply' => true])->assertFailed();
        $this->assertSame($old->getAttributes(), $old->fresh()->getAttributes());
    }
}
