<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\Driver;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleComplianceDocument;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ComplianceDocumentRenewalTest extends TestCase
{
    use RefreshDatabase;

    private User $dispatcher;

    private Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(\Carbon\Carbon::parse('2026-10-19 12:00:00', 'Asia/Ho_Chi_Minh'));
        Storage::fake('local');
        Storage::fake('compliance');
        Storage::fake('public');
        $this->seed(RbacSeeder::class);

        $this->dispatcher = User::factory()->create();
        $this->dispatcher->assignRole('dispatcher');

        $this->vehicle = Vehicle::query()->create([
            'license_plate' => '51B-123.45',
            'insurance_expires_at' => '2026-10-01',
        ]);
    }

    private function createInsuranceDoc(string $expiresAt = '2026-10-01'): VehicleComplianceDocument
    {
        $res = $this->actingAs($this->dispatcher)->post(
            "/api/vehicles/{$this->vehicle->id}/compliance-documents",
            [
                'doc_type' => 'insurance_certificate',
                'title' => 'Bảo hiểm TNDS',
                'expires_at' => $expiresAt,
                'file' => UploadedFile::fake()->createWithContent('bh-2025.pdf', "%PDF-1.4\n%old scan\n"),
            ],
            ['Accept' => 'application/json'],
        );
        $res->assertCreated();

        return VehicleComplianceDocument::query()->findOrFail($res->json('data.id'));
    }

    private function renew(VehicleComplianceDocument $doc, array $overrides = [])
    {
        return $this->actingAs($this->dispatcher)->post(
            "/api/vehicles/{$this->vehicle->id}/compliance-documents/{$doc->id}/renew",
            array_merge([
                'expires_at' => '2027-10-01',
                'issued_at' => '2026-09-25',
                'document_no' => 'BH-2026-001',
                'file' => UploadedFile::fake()->createWithContent('bh-2026.pdf', "%PDF-1.4\n%fake scan\n"),
            ], $overrides),
            ['Accept' => 'application/json'],
        );
    }

    public function test_renew_creates_new_active_document_and_keeps_old_one_with_its_file(): void
    {
        $old = $this->createInsuranceDoc();
        $oldAttachmentId = $old->attachments()->value('id');

        $res = $this->renew($old)->assertCreated();

        $newId = $res->json('data.id');
        $this->assertNotSame($old->id, $newId);
        $res->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.replaces_id', $old->id)
            ->assertJsonPath('data.document_no', 'BH-2026-001')
            ->assertJsonPath('data.title', 'Bảo hiểm TNDS');

        $this->assertSame('superseded', $old->fresh()->status);
        $this->assertNotNull(Attachment::query()->find($oldAttachmentId), 'File của bản cũ phải được giữ lại');

        $newAttachment = Attachment::query()->where('attachable_id', $newId)->firstOrFail();
        $this->assertNull($newAttachment->file_binary, 'Chứng từ mới chỉ lưu file trên disk');
        $this->assertSame('compliance', $newAttachment->disk);
        Storage::disk('compliance')->assertExists($newAttachment->path);

        $this->assertTrue(
            AuditLog::query()->where('event', 'vehicle_compliance_document.renewed')->exists(),
        );
    }

    public function test_renew_syncs_vehicle_expiry_column(): void
    {
        $old = $this->createInsuranceDoc();

        $this->renew($old)->assertCreated();

        $this->assertSame('2027-10-01', $this->vehicle->fresh()->insurance_expires_at->toDateString());
    }

    public function test_index_returns_only_active_documents_with_history(): void
    {
        $old = $this->createInsuranceDoc();
        $newId = $this->renew($old)->json('data.id');

        $res = $this->actingAs($this->dispatcher)
            ->getJson("/api/vehicles/{$this->vehicle->id}/compliance-documents")
            ->assertOk();

        $items = $res->json('data.items');
        $this->assertCount(1, $items);
        $this->assertSame($newId, $items[0]['id']);
        $this->assertCount(1, $items[0]['history']);
        $this->assertSame($old->id, $items[0]['history'][0]['id']);
        $this->assertSame('superseded', $items[0]['history'][0]['expiry']['state']);
    }

    public function test_renew_rejects_expiry_not_after_current_expiry(): void
    {
        $old = $this->createInsuranceDoc('2026-10-01');

        $this->renew($old, ['expires_at' => '2026-09-01', 'issued_at' => null])
            ->assertStatus(422)
            ->assertJsonValidationErrors('expires_at');

        $this->assertSame('active', $old->fresh()->status);
    }

    public function test_renew_requires_file_and_rejects_unsafe_file_types(): void
    {
        $old = $this->createInsuranceDoc();

        $this->renew($old, ['file' => null])->assertStatus(422)->assertJsonValidationErrors('file');
        $this->renew($old, ['file' => UploadedFile::fake()->create('x.html', 1, 'text/html')])
            ->assertStatus(422)
            ->assertJsonValidationErrors('file');
    }

    public function test_cannot_renew_superseded_document(): void
    {
        $old = $this->createInsuranceDoc();
        $this->renew($old)->assertCreated();

        $this->renew($old->fresh(), ['expires_at' => '2028-10-01'])->assertStatus(422);
    }

    public function test_deleting_renewed_document_restores_previous_one(): void
    {
        $old = $this->createInsuranceDoc();
        $newId = $this->renew($old)->json('data.id');

        $this->actingAs($this->dispatcher)
            ->deleteJson("/api/vehicles/{$this->vehicle->id}/compliance-documents/{$newId}")
            ->assertOk();

        $this->assertSame('active', $old->fresh()->status);
        $this->assertSame('2026-10-01', $this->vehicle->fresh()->insurance_expires_at->toDateString());
    }

    public function test_user_without_permission_cannot_renew(): void
    {
        $old = $this->createInsuranceDoc();
        $staff = User::factory()->create();
        $staff->assignRole('internal_user');

        $this->actingAs($staff)->post(
            "/api/vehicles/{$this->vehicle->id}/compliance-documents/{$old->id}/renew",
            ['expires_at' => '2027-10-01', 'file' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')],
            ['Accept' => 'application/json'],
        )->assertForbidden();
    }

    public function test_driver_license_renewal_syncs_driver_expiry(): void
    {
        $driver = Driver::query()->create(['full_name' => 'Nguyễn Văn A', 'license_expires_at' => '2026-10-10']);

        $docId = $this->actingAs($this->dispatcher)->post(
            "/api/drivers/{$driver->id}/compliance-documents",
            ['doc_type' => 'license', 'expires_at' => '2026-10-10'],
            ['Accept' => 'application/json'],
        )->assertCreated()->json('data.id');

        $this->actingAs($this->dispatcher)->post(
            "/api/drivers/{$driver->id}/compliance-documents/{$docId}/renew",
            ['expires_at' => '2036-10-10', 'file' => UploadedFile::fake()->image('gplx.jpg')],
            ['Accept' => 'application/json'],
        )->assertCreated();

        $this->assertSame('2036-10-10', $driver->fresh()->license_expires_at->toDateString());
    }

    public function test_library_lists_documents_with_owner_filters_and_summary(): void
    {
        $old = $this->createInsuranceDoc();
        $this->renew($old)->assertCreated();

        $other = Vehicle::query()->create(['license_plate' => '29A-999.99']);
        $other->complianceDocuments()->create(['doc_type' => 'inspection_certificate', 'expires_at' => now()->subDay()->toDateString()]);

        $res = $this->actingAs($this->dispatcher)
            ->getJson('/api/compliance-documents?owner_type=vehicle')
            ->assertOk();

        $this->assertSame(2, $res->json('data.meta.total'));
        $this->assertSame(1, $res->json('data.summary.exp'));
        $this->assertSame(1, $res->json('data.summary.superseded'));

        $this->actingAs($this->dispatcher)
            ->getJson('/api/compliance-documents?owner_type=vehicle&q=51B')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonPath('data.items.0.owner.label', '51B-123.45');

        $this->actingAs($this->dispatcher)
            ->getJson('/api/compliance-documents?owner_type=vehicle&status=all&q=51B')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 2);

        $this->actingAs($this->dispatcher)
            ->getJson('/api/compliance-documents?owner_type=vehicle&state=exp')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonPath('data.items.0.owner.label', '29A-999.99');
    }

    public function test_deleting_last_document_clears_owner_expiry(): void
    {
        $doc = $this->createInsuranceDoc();
        $this->actingAs($this->dispatcher)
            ->deleteJson("/api/vehicles/{$this->vehicle->id}/compliance-documents/{$doc->id}")
            ->assertOk();

        $this->assertNull($this->vehicle->fresh()->insurance_expires_at);
    }

    public function test_change_type_recalculates_both_owner_expiry_columns(): void
    {
        $remaining = $this->createInsuranceDoc('2026-09-01');
        $doc = $this->createInsuranceDoc();
        $this->actingAs($this->dispatcher)
            ->patchJson("/api/vehicles/{$this->vehicle->id}/compliance-documents/{$doc->id}", [
                'doc_type' => 'inspection_certificate', 'expires_at' => '2027-10-01',
            ])->assertOk();

        $vehicle = $this->vehicle->fresh();
        $this->assertSame('2026-09-01', $vehicle->insurance_expires_at->toDateString());
        $this->assertSame('2027-10-01', $vehicle->inspection_expires_at->toDateString());
        $this->actingAs($this->dispatcher)
            ->patchJson("/api/vehicles/{$vehicle->id}/compliance-documents/{$remaining->id}", [
                'doc_type' => 'other',
            ])->assertOk();
        $this->assertNull($vehicle->fresh()->insurance_expires_at);
    }

    public function test_removing_expiry_clears_owner_but_retains_other_active_document_expiry(): void
    {
        $older = $this->createInsuranceDoc('2026-09-01');
        $newer = $this->createInsuranceDoc();
        $this->actingAs($this->dispatcher)
            ->patchJson("/api/vehicles/{$this->vehicle->id}/compliance-documents/{$newer->id}", [
                'expires_at' => null,
            ])->assertOk();
        $this->assertSame('2026-09-01', $this->vehicle->fresh()->insurance_expires_at->toDateString());
        $this->deleteJson("/api/vehicles/{$this->vehicle->id}/compliance-documents/{$older->id}")->assertOk();
        $this->assertNull($this->vehicle->fresh()->insurance_expires_at);
    }

    public function test_driver_last_license_deletion_clears_expiry(): void
    {
        $driver = Driver::query()->create(['full_name' => 'Test driver']);
        $id = $this->actingAs($this->dispatcher)->postJson("/api/drivers/{$driver->id}/compliance-documents", [
            'doc_type' => 'license', 'expires_at' => '2027-10-01',
        ])->assertCreated()->json('data.id');
        $this->deleteJson("/api/drivers/{$driver->id}/compliance-documents/{$id}")->assertOk();
        $this->assertNull($driver->fresh()->license_expires_at);
    }

    public function test_renew_rejects_expired_new_date_and_preserves_old_document(): void
    {
        $doc = $this->createInsuranceDoc();
        $this->renew($doc, ['expires_at' => '2026-10-18'])->assertUnprocessable()
            ->assertJsonValidationErrors('expires_at');
        $this->assertSame('active', $doc->fresh()->status);
        $this->assertSame(1, $this->vehicle->complianceDocuments()->count());
        $this->assertCount(1, Storage::disk('compliance')->allFiles());
    }

    public function test_renew_accepts_today_but_rejects_equal_current_expiry(): void
    {
        $old = $this->createInsuranceDoc();
        $this->renew($old, ['expires_at' => '2026-10-19'])->assertCreated();
        $future = $this->createInsuranceDoc('2027-10-01');
        $this->renew($future)->assertUnprocessable()->assertJsonValidationErrors('expires_at');
    }

    public function test_renew_uses_vietnam_date_at_utc_day_boundary(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-19 18:00:00', 'UTC'));
        $doc = $this->createInsuranceDoc();
        $this->renew($doc, ['expires_at' => '2026-10-19'])->assertUnprocessable();
        $this->renew($doc, ['expires_at' => '2026-10-20'])->assertCreated()
            ->assertJsonPath('data.expiry.state', 'soon');
    }

    public function test_configured_private_disk_is_used_independently_of_default_disk(): void
    {
        config(['compliance.disk' => 'archive', 'filesystems.default' => 'public']);
        Storage::fake('archive');
        $doc = $this->createInsuranceDoc();
        $attachment = $doc->attachments()->firstOrFail();
        $this->assertSame('archive', $attachment->disk);
        Storage::disk('archive')->assertExists($attachment->path);
        Storage::disk('public')->assertMissing($attachment->path);
        $this->assertSame(url("/api/attachments/{$attachment->id}/download"), $attachment->url);
    }

    public function test_generic_attachment_upload_also_uses_private_compliance_storage(): void
    {
        $doc = $this->createInsuranceDoc();
        $response = $this->actingAs($this->dispatcher)->postJson('/api/attachments', [
            'attachable_type' => 'vehicle_compliance_document', 'attachable_id' => $doc->id,
            'file' => UploadedFile::fake()->image('extra-scan.png'),
        ])->assertCreated()->assertJsonPath('data.disk', 'compliance');
        $attachment = Attachment::findOrFail($response->json('data.id'));
        Storage::disk('compliance')->assertExists($attachment->path);
        $this->assertNull($attachment->file_binary);
    }

    public function test_download_falls_back_to_private_disk_when_database_binary_is_missing(): void
    {
        $attachment = $this->createInsuranceDoc()->attachments()->firstOrFail();
        $expected = Storage::disk('compliance')->get($attachment->path);
        $attachment->update(['file_binary' => null]);
        $this->getJson("/api/attachments/{$attachment->id}/download")->assertOk()->assertDownload()
            ->assertStreamedContent($expected);
    }

    public function test_download_reads_legacy_local_file_mislabeled_public(): void
    {
        $attachment = $this->createInsuranceDoc()->attachments()->firstOrFail();
        Storage::disk('local')->put($attachment->path, 'legacy scan');
        $attachment->update(['disk' => 'public', 'file_binary' => null]);
        $this->getJson("/api/attachments/{$attachment->id}/download")->assertOk()
            ->assertStreamedContent('legacy scan');
    }

    public function test_database_download_survives_missing_disk_and_denies_unprivileged_user(): void
    {
        $attachment = $this->createInsuranceDoc()->attachments()->firstOrFail();
        // Bản cũ chưa chuyển vẫn tải được trong giai đoạn triển khai.
        $attachment->update(['file_binary' => Storage::disk('compliance')->get($attachment->path)]);
        Storage::disk('compliance')->delete($attachment->path);
        $this->getJson("/api/attachments/{$attachment->id}/download")->assertOk()
            ->assertContent($attachment->file_binary);
        $user = User::factory()->create();
        $user->assignRole('internal_user');
        $this->actingAs($user)->getJson("/api/attachments/{$attachment->id}/download")->assertForbidden();
    }

    public function test_new_scan_download_uses_disk_and_reports_missing_file(): void
    {
        $attachment = $this->createInsuranceDoc()->attachments()->firstOrFail();
        $bytes = Storage::disk('compliance')->get($attachment->path);
        $this->assertNull($attachment->file_binary);
        $this->getJson("/api/attachments/{$attachment->id}/download")->assertOk()->assertStreamedContent($bytes);
        Storage::disk('compliance')->delete($attachment->path);
        $this->getJson("/api/attachments/{$attachment->id}/download")->assertNotFound();
    }

    public function test_failed_renewal_storage_rolls_back_document_and_expiry(): void
    {
        $doc = $this->createInsuranceDoc();
        $disk = \Mockery::mock(\Illuminate\Filesystem\FilesystemAdapter::class);
        $disk->shouldReceive('putFileAs')->once()->andReturn(false);
        Storage::shouldReceive('disk')->with('compliance')->andReturn($disk);
        $this->renew($doc)->assertStatus(500);
        $this->assertSame('active', $doc->fresh()->status);
        $this->assertSame(1, $this->vehicle->complianceDocuments()->count());
        $this->assertSame('2026-10-01', $this->vehicle->fresh()->insurance_expires_at->toDateString());
    }

    public function test_adding_scan_to_undated_document_preserves_manually_entered_owner_expiry(): void
    {
        $id = $this->actingAs($this->dispatcher)
            ->postJson("/api/vehicles/{$this->vehicle->id}/compliance-documents", [
                'doc_type' => 'insurance_certificate',
            ])->assertCreated()->json('data.id');
        $this->postJson('/api/attachments', [
            'attachable_type' => 'vehicle_compliance_document', 'attachable_id' => $id,
            'file' => UploadedFile::fake()->image('scan.png'),
        ])->assertCreated();
        $this->assertSame('2026-10-01', $this->vehicle->fresh()->insurance_expires_at->toDateString());
    }

    public function test_public_compliance_disk_is_rejected_without_creating_document(): void
    {
        config(['compliance.disk' => 'public']);
        $this->actingAs($this->dispatcher)
            ->postJson("/api/vehicles/{$this->vehicle->id}/compliance-documents", [
                'doc_type' => 'insurance_certificate', 'expires_at' => '2027-10-01',
                'file' => UploadedFile::fake()->image('scan.png'),
            ])->assertStatus(500);
        $this->assertSame(0, $this->vehicle->complianceDocuments()->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_reading_documents_does_not_exhaust_compliance_mutation_limit(): void
    {
        $doc = $this->createInsuranceDoc();
        for ($i = 0; $i < 35; $i++) {
            $this->actingAs($this->dispatcher)
                ->getJson("/api/vehicles/{$this->vehicle->id}/compliance-documents")->assertOk();
        }

        $this->renew($doc)->assertCreated();
    }
}
