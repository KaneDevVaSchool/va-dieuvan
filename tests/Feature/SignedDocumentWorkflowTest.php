<?php

namespace Tests\Feature;

use App\Jobs\ProcessSignedDocumentPipelineJob;
use App\Models\DispatchRequest;
use App\Models\SignedDocumentVersion;
use App\Models\User;
use App\Services\SignedDocuments\SignedDocumentPipelineService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SignedDocumentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function approvedRequest(User $requester): DispatchRequest
    {
        return DispatchRequest::create([
            'requester_id' => $requester->id,
            'trip_type' => 'business',
            'origin' => 'A',
            'destination' => 'B',
            'depart_at' => now()->addDays(3),
            'arrive_by' => now()->addDays(3)->addHours(2),
            'status' => 'approved',
            'source_channel' => 'portal',
            'is_urgent' => false,
            'paper_status' => 'pending',
            'wizard_snapshot' => [],
        ]);
    }

    public function test_portal_upload_creates_version_and_dispatches_job(): void
    {
        $this->seed(RbacSeeder::class);
        Queue::fake();

        $requester = User::factory()->create(['is_active' => true]);
        Sanctum::actingAs($requester);

        $dr = $this->approvedRequest($requester);

        $file = UploadedFile::fake()->create('signed.pdf', 100, 'application/pdf');

        $response = $this->postJson("/api/portal/dispatch-requests/{$dr->id}/signed-paper", [
            'file' => $file,
        ]);

        $response->assertCreated();
        $this->assertDatabaseCount('signed_document_versions', 1);

        $version = SignedDocumentVersion::query()->first();
        $this->assertSame(1, $version->version_no);
        $this->assertTrue($version->is_current);

        Queue::assertPushed(ProcessSignedDocumentPipelineJob::class);
    }

    public function test_second_upload_increments_version(): void
    {
        $this->seed(RbacSeeder::class);
        Queue::fake();

        $requester = User::factory()->create(['is_active' => true]);
        Sanctum::actingAs($requester);

        $dr = $this->approvedRequest($requester);

        $this->postJson("/api/portal/dispatch-requests/{$dr->id}/signed-paper", [
            'file' => UploadedFile::fake()->create('v1.pdf', 50, 'application/pdf'),
        ])->assertCreated();

        $this->postJson("/api/portal/dispatch-requests/{$dr->id}/signed-paper", [
            'file' => UploadedFile::fake()->create('v2.pdf', 50, 'application/pdf'),
        ])->assertCreated();

        $this->assertSame(2, SignedDocumentVersion::query()->count());
        $current = SignedDocumentVersion::query()->where('is_current', true)->first();
        $this->assertSame(2, $current->version_no);
    }

    public function test_pipeline_completes_ocr_and_sets_verification(): void
    {
        $this->seed(RbacSeeder::class);

        $requester = User::factory()->create(['is_active' => true]);
        $dr = $this->approvedRequest($requester);

        Sanctum::actingAs($requester);
        $this->postJson("/api/portal/dispatch-requests/{$dr->id}/signed-paper", [
            'file' => UploadedFile::fake()->create('signed.pdf', 100, 'application/pdf'),
        ])->assertCreated();

        $version = SignedDocumentVersion::query()->where('is_current', true)->first();
        $this->assertNotNull($version);

        app(SignedDocumentPipelineService::class)->run($version->id);

        $version->refresh();
        $this->assertSame('completed', $version->ocr_status);
        $this->assertNotNull($version->verification_status);
        $this->assertNotNull($version->attachment?->fresh()->ocr_text);
    }

    public function test_staff_manual_verify_requires_permission(): void
    {
        $this->seed(RbacSeeder::class);

        $requester = User::factory()->create(['is_active' => true]);
        $dr = $this->approvedRequest($requester);

        Sanctum::actingAs($requester);
        $this->postJson("/api/portal/dispatch-requests/{$dr->id}/signed-paper", [
            'file' => UploadedFile::fake()->create('signed.pdf', 100, 'application/pdf'),
        ]);

        $version = SignedDocumentVersion::query()->first();
        app(SignedDocumentPipelineService::class)->run($version->id);
        $version->refresh();

        $dispatcher = User::factory()->create(['is_active' => true]);
        $dispatcher->assignRole('dispatcher');
        Sanctum::actingAs($dispatcher);

        $this->postJson("/api/signed-document-versions/{$version->id}/verify", [
            'decision' => 'approve',
            'note' => 'OK',
        ])->assertOk();

        $this->assertSame('verified', $version->fresh()->verification_status);
    }
}
