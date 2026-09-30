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
        Storage::fake('local');
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
                'file' => UploadedFile::fake()->create('bh-2025.pdf', 50, 'application/pdf'),
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
        $this->assertNotEmpty($newAttachment->file_binary, 'File vẫn lưu song song trong DB');
        Storage::disk('local')->assertExists($newAttachment->path);

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
}
