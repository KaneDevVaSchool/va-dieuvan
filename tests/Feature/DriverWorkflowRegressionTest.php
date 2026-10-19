<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\ChecklistReminderSlot;
use App\Models\ChecklistSubmission;
use App\Models\ChecklistTemplate;
use App\Models\Driver;
use App\Models\ExpenseDocument;
use App\Models\TripCost;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Checklist\ChecklistPrintService;
use App\Services\Checklist\ChecklistReminderService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Regression coverage for driver checklist and financial document integrity. */
class DriverWorkflowRegressionTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    private Driver $driver;

    private Vehicle $a;

    private Vehicle $b;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        Storage::fake('compliance');
        Storage::fake('local');
        Notification::fake();
        Carbon::setTestNow(Carbon::parse('2026-10-05 01:00:00', 'UTC'));
        $this->actor = User::factory()->create(['is_active' => true]);
        $this->actor->assignRole('driver');
        $this->driver = Driver::create(['user_id' => $this->actor->id, 'full_name' => 'Audit Driver', 'employment_status' => 'active', 'availability_status' => 'available']);
        $this->a = Vehicle::create(['license_plate' => 'AUDIT-A', 'default_driver_id' => $this->driver->id]);
        $this->b = Vehicle::create(['license_plate' => 'AUDIT-B']);
        $this->actingAs($this->actor);
    }

    private function payment(array $lines, array $headers = []): array
    {
        return $this->postJson('/api/driver/expenses', array_merge([
            'doc_type' => 'payment_request', 'payment_kind' => 'payment', 'payment_method' => 'cash', 'lines' => $lines,
        ], $headers))->assertCreated()->json('data');
    }

    public function test_server_timestamps_ignore_client_clock(): void
    {
        $template = ChecklistTemplate::where('code', ChecklistTemplate::CODE_DAILY)->firstOrFail();
        $doc = $this->postJson('/api/driver/checklists', ['template_id' => $template->id, 'vehicle_id' => $this->a->id])->assertCreated()->json('data');
        $this->assertTrue(Carbon::parse($doc['started_at'])->equalTo(Carbon::now()));
        Carbon::setTestNow(Carbon::parse('2026-10-05 01:03:00', 'UTC'));
        $done = $this->postJson("/api/driver/checklists/{$doc['id']}/submit", ['lock_version' => $doc['lock_version'], 'confirm' => true,
            'client_submitted_at' => '2020-01-01T00:00:00Z',
            'items' => array_map(fn ($i) => ['id' => $i['id'], 'result' => 'ok', 'checked_at' => '2020-01-01T00:00:00Z'], $doc['items']),
        ])->assertOk()->json('data');
        $this->assertTrue(Carbon::parse($done['submitted_at'])->equalTo(Carbon::now()));
        foreach ($done['items'] as $item) {
            $this->assertTrue(Carbon::parse($item['checked_at'])->equalTo(Carbon::now()));
        }
    }

    private function existingCost(array $data = []): TripCost
    {
        return TripCost::create(array_merge(['vehicle_id' => $this->b->id, 'created_by' => $this->actor->id, 'type' => 'fuel',
            'amount' => 100000, 'currency' => 'VND', 'status' => 'submitted', 'reported_on' => '2026-10-05'], $data));
    }

    public function test_linked_cost_uses_ledger_vehicle_despite_different_header(): void
    {
        $cost = $this->existingCost();
        $doc = $this->payment([['trip_cost_id' => $cost->id]], ['vehicle_id' => $this->a->id]);
        $this->assertSame($this->b->id, $doc['lines'][0]['vehicle_id']);
        $this->assertSame($this->b->id, $cost->fresh()->vehicle_id);
    }

    public function test_repeated_cost_is_rejected_in_same_document(): void
    {
        $cost = $this->existingCost();
        $this->postJson('/api/driver/expenses', ['doc_type' => 'payment_request', 'payment_kind' => 'payment',
            'lines' => [['trip_cost_id' => $cost->id], ['trip_cost_id' => $cost->id]],
        ])->assertUnprocessable()->assertJsonValidationErrors(['lines.0.trip_cost_id']);
        $this->assertSame(1, TripCost::count());
    }

    public function test_existing_no_invoice_cost_retains_metadata_and_generates_statement(): void
    {
        $cost = $this->existingCost(['has_invoice' => false, 'seller_name' => 'Audit Seller']);
        $doc = $this->payment([['trip_cost_id' => $cost->id]]);
        $this->assertFalse($doc['lines'][0]['has_invoice']);
        $this->assertSame('Audit Seller', $doc['lines'][0]['seller_name']);
        $this->postJson("/api/driver/expenses/{$doc['id']}/submit", ['lock_version' => $doc['lock_version']])->assertOk();
        $this->assertSame(1, ExpenseDocument::where('parent_id', $doc['id'])->count());
    }

    public function test_cancelling_parent_cancels_statement_and_removes_owned_cost(): void
    {
        $doc = $this->payment([['vehicle_id' => $this->a->id, 'category' => 'Meal', 'amount' => 80000,
            'has_invoice' => false, 'seller_name' => 'Audit Seller', 'purchased_on' => '2026-10-05']]);
        $this->postJson("/api/driver/expenses/{$doc['id']}/submit", ['lock_version' => $doc['lock_version']])->assertOk();
        $this->postJson("/api/driver/expenses/{$doc['id']}/cancel")->assertOk();
        $this->assertSame('cancelled', ExpenseDocument::where('parent_id', $doc['id'])->value('status'));
        $this->assertFalse(TripCost::whereKey($doc['lines'][0]['trip_cost_id'])->exists());
    }

    public function test_auto_statement_requires_seller_and_purchase_date(): void
    {
        $doc = $this->payment([['vehicle_id' => $this->a->id, 'category' => 'Meal', 'amount' => 80000, 'has_invoice' => false]]);
        $this->postJson("/api/driver/expenses/{$doc['id']}/submit", ['lock_version' => $doc['lock_version']])
            ->assertUnprocessable()->assertJsonValidationErrors(['lines.0.seller_name', 'lines.0.purchased_on']);
        $this->assertSame(0, ExpenseDocument::where('parent_id', $doc['id'])->count());
        $this->assertSame('draft', ExpenseDocument::findOrFail($doc['id'])->status);
    }

    public function test_reads_do_not_exhaust_expense_submit_throttle(): void
    {
        $doc = $this->postJson('/api/driver/expenses', ['doc_type' => 'proposal', 'purpose' => 'Audit',
            'lines' => [['vehicle_id' => $this->a->id, 'category' => 'Audit', 'amount' => 100000]],
        ])->assertCreated()->json('data');
        for ($i = 0; $i < 35; $i++) {
            $this->getJson("/api/driver/expenses/{$doc['id']}")->assertOk();
        }
        $this->postJson("/api/driver/expenses/{$doc['id']}/submit", ['lock_version' => $doc['lock_version']])->assertOk();
    }

    public function test_autosaves_do_not_exhaust_checklist_submit_throttle(): void
    {
        $template = ChecklistTemplate::where('code', ChecklistTemplate::CODE_DAILY)->firstOrFail();
        $doc = $this->postJson('/api/driver/checklists', ['template_id' => $template->id, 'vehicle_id' => $this->a->id])->assertCreated()->json('data');
        for ($i = 0; $i < 16; $i++) {
            $doc = $this->patchJson("/api/driver/checklists/{$doc['id']}", ['lock_version' => $doc['lock_version'],
                'items' => [['id' => $doc['items'][$i]['id'], 'result' => 'ok']],
            ])->assertOk()->json('data');
        }
        $this->postJson("/api/driver/checklists/{$doc['id']}/submit", ['lock_version' => $doc['lock_version'], 'confirm' => true,
            'items' => array_map(fn ($i) => ['id' => $i['id'], 'result' => 'ok'], $doc['items']),
        ])->assertOk();
    }

    public function test_midnight_escalation_uses_original_slot_date(): void
    {
        Notification::fake();
        ChecklistReminderSlot::query()->update(['is_active' => false]);
        $template = ChecklistTemplate::where('code', ChecklistTemplate::CODE_DAILY)->firstOrFail();
        ChecklistReminderSlot::create(['template_id' => $template->id, 'remind_at' => '23:50', 'weekdays' => [1, 2, 3, 4, 5, 6, 7], 'escalate_after_minutes' => 20, 'is_active' => true]);
        $service = app(ChecklistReminderService::class);
        $this->assertSame(1, $service->run(Carbon::parse('2026-10-05 23:52', 'Asia/Ho_Chi_Minh'))['reminded']);
        $dispatcher = User::factory()->create(['is_active' => true]);
        $dispatcher->assignRole('dispatcher');
        $this->assertSame(1, $service->run(Carbon::parse('2026-10-06 00:12', 'Asia/Ho_Chi_Minh'))['escalated']);
    }

    public function test_daily_print_excludes_weekly_from_another_vehicle_and_future(): void
    {
        $daily = ChecklistTemplate::where('code', ChecklistTemplate::CODE_DAILY)->firstOrFail();
        $weekly = ChecklistTemplate::where('code', ChecklistTemplate::CODE_WEEKLY)->firstOrFail();
        $d = $this->postJson('/api/driver/checklists', ['template_id' => $daily->id, 'vehicle_id' => $this->a->id])->assertCreated()->json('data');
        $w = $this->postJson('/api/driver/checklists', ['template_id' => $weekly->id, 'vehicle_id' => $this->b->id])->assertCreated()->json('data');
        $weeklyDoc = ChecklistSubmission::findOrFail($w['id']);
        $weeklyDoc->update(['status' => 'submitted', 'submitted_at' => '2026-10-08 01:00:00']);
        $weeklyDoc->items()->update(['result' => 'ok']);
        $print = app(ChecklistPrintService::class)->data(ChecklistSubmission::findOrFail($d['id']));
        $inspection = collect($print['rows'])->firstWhere('title', 'Đăng kiểm');
        $this->assertStringNotContainsString($weeklyDoc->code, (string) $inspection['note']);
    }

    public function test_historical_daily_print_uses_prior_weekly_for_same_vehicle_only(): void
    {
        $daily = ChecklistTemplate::where('code', ChecklistTemplate::CODE_DAILY)->firstOrFail();
        $weekly = ChecklistTemplate::where('code', ChecklistTemplate::CODE_WEEKLY)->firstOrFail();
        $prior = app(\App\Services\Checklist\ChecklistSubmissionService::class)->start($this->actor, ['template_id' => $weekly->id, 'vehicle_id' => $this->a->id]);
        $prior->update(['status' => 'submitted', 'submitted_at' => '2026-10-04 01:00:00']);
        $prior->items()->update(['result' => 'ok']);
        $d = app(\App\Services\Checklist\ChecklistSubmissionService::class)->start($this->actor, ['template_id' => $daily->id, 'vehicle_id' => $this->a->id]);
        foreach ([$this->a, $this->b] as $i => $vehicle) {
            $other = app(\App\Services\Checklist\ChecklistSubmissionService::class)->start($this->actor, ['template_id' => $weekly->id, 'vehicle_id' => $vehicle->id]);
            $other->update(['status' => 'submitted', 'submitted_at' => $i === 0 ? '2026-10-08 01:00:00' : '2026-10-04 03:00:00']);
            $other->items()->update(['result' => 'ng']);
        }
        $print = app(ChecklistPrintService::class)->data($d);
        $inspection = collect($print['rows'])->firstWhere('title', 'Đăng kiểm');
        $this->assertSame('ok', $inspection['result']);
        $this->assertStringContainsString($prior->code, $inspection['note']);
    }

    private function submittedPaymentWithStatement(): array
    {
        $doc = $this->payment([['vehicle_id' => $this->a->id, 'category' => 'Meal', 'amount' => 80000,
            'has_invoice' => false, 'seller_name' => 'Seller', 'purchased_on' => '2026-10-05']]);
        $this->postJson("/api/driver/expenses/{$doc['id']}/submit", ['lock_version' => $doc['lock_version']])->assertOk();

        return [$doc, ExpenseDocument::where('parent_id', $doc['id'])->firstOrFail()];
    }

    public function test_cancellation_is_atomic_when_child_is_signed(): void
    {
        [$doc, $child] = $this->submittedPaymentWithStatement();
        $child->update(['status' => 'signed']);
        $this->getJson("/api/driver/expenses/{$doc['id']}")->assertOk()->assertJsonPath('data.can.cancel', false);
        $this->postJson("/api/driver/expenses/{$doc['id']}/cancel")->assertConflict();
        $this->assertSame('submitted', ExpenseDocument::findOrFail($doc['id'])->status);
        $this->assertTrue(TripCost::whereKey($doc['lines'][0]['trip_cost_id'])->exists());
    }

    public function test_statement_cannot_be_cancelled_or_paid_independently(): void
    {
        [$doc, $child] = $this->submittedPaymentWithStatement();
        $this->postJson("/api/driver/expenses/{$child->id}/cancel")->assertConflict();
        $accountant = User::factory()->create(['is_active' => true]);
        $accountant->assignRole('accountant');
        $this->actingAs($accountant)->getJson("/api/expenses/{$child->id}")->assertOk()
            ->assertJsonPath('data.can.mark_paid', false)->assertJsonPath('data.can.cancel', false);
        $this->postJson("/api/expenses/{$child->id}/mark-paid")->assertConflict();
        $this->postJson("/api/expenses/{$doc['id']}/mark-paid")->assertOk();
        $this->assertSame('paid', $child->fresh()->status);
    }

    public function test_rejection_and_resubmission_reuse_child_and_costs(): void
    {
        [$doc, $child] = $this->submittedPaymentWithStatement();
        $child->update(['status' => 'signed']);
        $accountant = User::factory()->create(['is_active' => true]);
        $accountant->assignRole('accountant');
        $this->actingAs($accountant)->postJson("/api/expenses/{$doc['id']}/reject", ['reason' => 'Need details'])->assertOk();
        $this->assertSame('rejected', $child->fresh()->status);
        $this->actingAs($this->actor)->postJson("/api/driver/expenses/{$doc['id']}/submit", [
            'lock_version' => ExpenseDocument::findOrFail($doc['id'])->lock_version,
        ])->assertOk();
        $this->assertSame('submitted', $child->fresh()->status);
        $this->assertSame(1, ExpenseDocument::where('parent_id', $doc['id'])->count());
        $this->assertSame(1, TripCost::count());
    }

    public function test_legacy_duplicate_lines_cannot_be_submitted(): void
    {
        $doc = $this->payment([['trip_cost_id' => $this->existingCost()->id]]);
        $model = ExpenseDocument::findOrFail($doc['id']);
        $model->lines()->firstOrFail()->replicate()->save();
        $this->postJson("/api/driver/expenses/{$doc['id']}/submit", ['lock_version' => $doc['lock_version']])
            ->assertUnprocessable()->assertJsonValidationErrors(['lines.1.trip_cost_id']);
        $this->assertSame('draft', $model->fresh()->status);
    }

    public function test_accountant_can_access_financial_pages_without_checklist_or_admin_permissions(): void
    {
        $accountant = User::factory()->create(['is_active' => true]);
        $accountant->assignRole('accountant');
        $this->actingAs($accountant)->getJson('/api/expenses')->assertOk();
        $this->getJson('/api/vehicle-cost-ledger?date_from=2026-10-01&date_to=2026-10-31')->assertOk();
        $this->getJson('/api/checklists')->assertForbidden();
        $this->getJson('/api/admin/users')->assertForbidden();
    }

    public function test_new_scan_is_private_disk_only_and_download_checks_owner(): void
    {
        $doc = $this->postJson('/api/driver/expenses', ['doc_type' => 'proposal', 'purpose' => 'Audit',
            'lines' => [['vehicle_id' => $this->a->id, 'amount' => 100000]],
        ])->assertCreated()->json('data');
        $this->postJson("/api/driver/expenses/{$doc['id']}/submit", ['lock_version' => $doc['lock_version']])->assertOk();
        $this->post("/api/driver/expenses/{$doc['id']}/signed-scan", ['file' => UploadedFile::fake()->create('scan.pdf', 10, 'application/pdf')], ['Accept' => 'application/json'])->assertOk();
        $attachment = Attachment::where('attachable_type', ExpenseDocument::class)->firstOrFail();
        $this->assertSame('compliance', $attachment->disk);
        $this->assertNull($attachment->file_binary);
        $bytes = Storage::disk('compliance')->get($attachment->path);
        $this->assertSame(hash('sha256', $bytes), $attachment->sha256);
        $this->assertSame('private', config('filesystems.disks.compliance.visibility'));
        if (PHP_OS_FAMILY !== 'Windows') {
            $this->assertSame('private', Storage::disk('compliance')->getVisibility($attachment->path));
        }
        $this->assertStringContainsString('/api/attachments/', $attachment->url);
        $this->get("/api/attachments/{$attachment->id}/download")->assertOk();
        $this->actor->givePermissionTo('attachment.upload');
        $this->deleteJson("/api/attachments/{$attachment->id}")->assertConflict();
        $other = User::factory()->create(['is_active' => true]);
        $other->assignRole('driver');
        $this->actingAs($other)->get("/api/attachments/{$attachment->id}/download")->assertForbidden();
    }

    public function test_delayed_midnight_escalation_keeps_original_week_and_deduplicates(): void
    {
        ChecklistReminderSlot::query()->update(['is_active' => false]);
        $template = ChecklistTemplate::where('code', ChecklistTemplate::CODE_WEEKLY)->firstOrFail();
        ChecklistReminderSlot::create(['template_id' => $template->id, 'remind_at' => '23:50', 'weekdays' => [7], 'escalate_after_minutes' => 20, 'is_active' => true]);
        $dispatcher = User::factory()->create(['is_active' => true]);
        $dispatcher->assignRole('dispatcher');
        $service = app(ChecklistReminderService::class);
        $this->assertSame(1, $service->run(Carbon::parse('2026-10-05 00:35', 'Asia/Ho_Chi_Minh'))['escalated']);
        Notification::assertSentTo($dispatcher, \App\Notifications\ChecklistOverdueDigestNotification::class,
            fn ($n) => str_contains($n->toArray($dispatcher)['body'], '40'));
        $this->assertSame(0, $service->run(Carbon::parse('2026-10-05 00:36', 'Asia/Ho_Chi_Minh'))['escalated']);
    }

    public function test_checklist_photo_is_private_disk_only_and_can_be_removed(): void
    {
        $template = ChecklistTemplate::where('code', ChecklistTemplate::CODE_DAILY)->firstOrFail();
        $doc = $this->postJson('/api/driver/checklists', ['template_id' => $template->id, 'vehicle_id' => $this->a->id])->assertCreated()->json('data');
        $itemId = $doc['items'][0]['id'];
        $upload = $this->post("/api/driver/checklists/{$doc['id']}/items/{$itemId}/evidence", [
            'file' => UploadedFile::fake()->image('photo.jpg'),
        ], ['Accept' => 'application/json'])->assertCreated();
        $attachment = Attachment::findOrFail($upload->json('data.id'));
        $this->assertSame('compliance', $attachment->disk);
        $this->assertNull($attachment->file_binary);
        $this->assertNotEmpty($attachment->sha256);
        Storage::disk('compliance')->assertExists($attachment->path);
        $this->get("/api/attachments/{$attachment->id}/download")->assertOk();
        $this->actor->givePermissionTo('attachment.upload');
        $this->deleteJson("/api/attachments/{$attachment->id}")->assertConflict();
        $this->deleteJson("/api/driver/checklists/{$doc['id']}/items/{$itemId}/evidence/{$attachment->id}")->assertOk();
        Storage::disk('compliance')->assertMissing($attachment->path);
        $this->assertDatabaseMissing('attachments', ['id' => $attachment->id]);
    }

    public function test_disabled_checklist_module_suppresses_reminders(): void
    {
        $this->mock(\App\Services\FeatureToggleService::class)->shouldReceive('isEnabled')
            ->with('module.driver_checklist')->once()->andReturnFalse();
        $result = app(ChecklistReminderService::class)->run(Carbon::parse('2026-10-05 05:31', 'Asia/Ho_Chi_Minh'));
        $this->assertSame(['reminded' => 0, 'escalated' => 0], $result);
        Notification::assertNothingSent();
    }

    public function test_failed_private_write_does_not_create_attachment_metadata(): void
    {
        $disk = \Mockery::mock(\Illuminate\Filesystem\FilesystemAdapter::class);
        $disk->shouldReceive('putFileAs')->once()->andReturnFalse();
        Storage::set('compliance', $disk);
        try {
            app(\App\Services\Attachments\AttachmentStoreService::class)->store(
                new ExpenseDocument(['id' => 123]), UploadedFile::fake()->create('scan.pdf', 10),
                $this->actor->id, 'signed_scan', 'expenses');
            $this->fail('Expected failure for an unwritable private disk.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('private', $e->getMessage());
        }
        $this->assertSame(0, Attachment::count());
    }

    public function test_accountant_reads_do_not_exhaust_mark_paid_limit(): void
    {
        [$doc] = $this->submittedPaymentWithStatement();
        $accountant = User::factory()->create(['is_active' => true]);
        $accountant->assignRole('accountant');
        $this->actingAs($accountant);
        for ($i = 0; $i < 35; $i++) {
            $this->getJson("/api/expenses/{$doc['id']}")->assertOk();
        }
        $this->postJson("/api/expenses/{$doc['id']}/mark-paid")->assertOk();
    }

    public function test_auto_statement_cannot_duplicate_cost_held_by_another_statement(): void
    {
        $cost = $this->existingCost(['has_invoice' => false, 'seller_name' => 'Seller']);
        $statement = $this->postJson('/api/driver/expenses', ['doc_type' => 'no_invoice_statement',
            'lines' => [['trip_cost_id' => $cost->id]],
        ])->assertCreated()->json('data');
        $this->postJson("/api/driver/expenses/{$statement['id']}/submit", ['lock_version' => $statement['lock_version']])->assertOk();
        $doc = $this->payment([['trip_cost_id' => $cost->id]]);
        $this->postJson("/api/driver/expenses/{$doc['id']}/submit", ['lock_version' => $doc['lock_version']])
            ->assertUnprocessable()->assertJsonValidationErrors(['lines.0.trip_cost_id']);
        $this->assertSame('draft', ExpenseDocument::findOrFail($doc['id'])->status);
        $this->assertSame(0, ExpenseDocument::where('parent_id', $doc['id'])->count());
    }

    public function test_cancellation_keeps_confirmed_and_preexisting_costs(): void
    {
        $existing = $this->existingCost();
        $doc = $this->payment([['trip_cost_id' => $existing->id], ['vehicle_id' => $this->a->id, 'amount' => 50000]]);
        $ownedId = $doc['lines'][1]['trip_cost_id'];
        TripCost::whereKey($ownedId)->update(['status' => 'confirmed']);
        $this->postJson("/api/driver/expenses/{$doc['id']}/cancel")->assertOk();
        $this->assertTrue(TripCost::whereKey($existing->id)->exists());
        $this->assertSame('confirmed', TripCost::findOrFail($ownedId)->status);
    }
}
