<?php

namespace Tests\Feature\Checklist;

use App\Models\ChecklistSubmission;
use App\Models\ChecklistSubmissionItem;
use App\Models\ChecklistTemplate;
use App\Models\Driver;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\ChecklistCriticalFailedNotification;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DriverChecklistFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $driverUser;

    private Driver $driver;

    private Vehicle $vehicle;

    private User $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        Storage::fake('local');
        Storage::fake('compliance');
        Carbon::setTestNow(Carbon::parse('2026-10-05 00:10:00', 'UTC')); // 07:10 thứ Hai giờ VN

        $this->driverUser = User::factory()->create(['is_active' => true]);
        $this->driverUser->assignRole('driver');
        $this->driver = Driver::query()->create([
            'user_id' => $this->driverUser->id,
            'full_name' => 'Nguyễn Văn Tài',
            'phone' => '0900000001',
            'employment_status' => 'active',
            'availability_status' => 'available',
        ]);
        $this->vehicle = Vehicle::query()->create([
            'license_plate' => '51B-12345',
            'type' => 'Xe 29 chỗ',
            'seat_count' => 29,
            'status' => 'ready',
            'default_driver_id' => $this->driver->id,
        ]);

        $this->dispatcher = User::factory()->create(['is_active' => true]);
        $this->dispatcher->assignRole('dispatcher');
    }

    private function dailyTemplate(): ChecklistTemplate
    {
        return ChecklistTemplate::query()->where('code', ChecklistTemplate::CODE_DAILY)->firstOrFail();
    }

    private function start(): array
    {
        $res = $this->actingAs($this->driverUser)->postJson('/api/driver/checklists', [
            'template_id' => $this->dailyTemplate()->id,
            'vehicle_id' => $this->vehicle->id,
            'location_text' => 'Bãi xe cơ sở 1',
        ]);
        $res->assertCreated();

        return $res->json('data');
    }

    public function test_templates_are_seeded_from_reference_sheet(): void
    {
        $this->assertSame(30, $this->dailyTemplate()->items()->count());
        $weekly = ChecklistTemplate::query()->where('code', ChecklistTemplate::CODE_WEEKLY)->firstOrFail();
        $this->assertSame(16, $weekly->items()->count());
        $this->assertSame(2, ChecklistTemplate::query()->withCount('reminderSlots')->get()->sum('reminder_slots_count'));
    }

    public function test_driver_completes_checklist_with_critical_ng_and_dispatcher_is_notified(): void
    {
        Notification::fake();

        $data = $this->start();
        $this->assertSame('CL-261005-1', $data['code']);
        $this->assertSame('2026-W41', $data['period_key']);
        $this->assertSame('weekly', $data['period_type']);
        $this->assertSame('51B-12345', $data['trip_info']['license_plate']);
        $this->assertCount(30, $data['items']);

        $id = $data['id'];
        $items = collect($data['items']);
        $brake = $items->firstWhere('title', 'Phanh');
        $this->assertFalse($brake['requires_photo_on_ng']);
        // A draft created before the policy change must also allow submitting without photos.
        ChecklistSubmissionItem::findOrFail($brake['id'])->update(['requires_photo_on_ng' => true]);

        // Nộp khi chưa tick đủ → 422.
        $this->postJson("/api/driver/checklists/{$id}/submit", ['lock_version' => $data['lock_version'], 'confirm' => true])
            ->assertStatus(422);

        $payload = $items->map(fn ($i) => ['id' => $i['id'], 'result' => $i['id'] === $brake['id'] ? 'ng' : 'ok'])->values()->all();
        $saved = $this->patchJson("/api/driver/checklists/{$id}", ['lock_version' => $data['lock_version'], 'items' => $payload]);
        $saved->assertOk();
        $lock = $saved->json('data.lock_version');
        $this->assertNotNull(collect($saved->json('data.items'))->firstWhere('id', $brake['id'])['checked_at']);

        // Lock version cũ → 409.
        $this->patchJson("/api/driver/checklists/{$id}", ['lock_version' => $data['lock_version'], 'note' => 'x'])->assertStatus(409);

        // NG vẫn bắt buộc ghi chú; ảnh không còn là điều kiện nộp.
        $res = $this->postJson("/api/driver/checklists/{$id}/submit", ['lock_version' => $lock, 'confirm' => true]);
        $res->assertStatus(422);
        $res->assertJsonValidationErrors(["items.{$brake['id']}.note"]);
        $res->assertJsonMissingValidationErrors(["items.{$brake['id']}.photo"]);

        $submit = $this->postJson("/api/driver/checklists/{$id}/submit", [
            'lock_version' => $lock,
            'confirm' => true,
            'items' => [['id' => $brake['id'], 'note' => 'Phanh tay yếu, đã báo điều hành đổi xe']],
        ]);
        $submit->assertOk();
        $submit->assertJsonPath('data.status', 'submitted');
        $submit->assertJsonPath('data.result', 'fail');
        $submit->assertJsonPath('data.critical_ng_count', 1);
        $submittedBrake = collect($submit->json('data.items'))->firstWhere('id', $brake['id']);
        $this->assertSame([], $submittedBrake['attachments']);
        $this->assertFalse($submittedBrake['requires_photo_on_ng']);
        $this->assertNotNull($submit->json('data.submitted_at'));

        Notification::assertSentTo($this->dispatcher, ChecklistCriticalFailedNotification::class);

        // Đã nộp → không sửa được.
        $this->patchJson("/api/driver/checklists/{$id}", ['lock_version' => $submit->json('data.lock_version'), 'note' => 'x'])
            ->assertStatus(409);

        $overview = $this->getJson('/api/driver/checklists/overview')->assertOk();
        $daily = collect($overview->json('data.periods'))->firstWhere('template.code', ChecklistTemplate::CODE_DAILY);
        $this->assertSame('fail', $daily['state']);
        $this->assertSame('51B-12345', $overview->json('data.failed_today.0.license_plate'));

        $this->get("/api/driver/checklists/{$id}/pdf")->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_critical_ng_submit_succeeds_and_alerts_all_dispatchers_when_mail_fails(): void
    {
        config(['queue.default' => 'sync']);
        $secondDispatcher = User::factory()->create(['is_active' => true]);
        $secondDispatcher->assignRole('dispatcher');

        // Exercise real notification delivery and database persistence, without SMTP.
        $this->mock(MailChannel::class, function ($mock) {
            $mock->shouldReceive('send')->twice()
                ->andThrow(new \RuntimeException('SMTP authentication failed'));
        });

        $data = $this->start();
        $critical = collect($data['items'])->firstWhere('severity', 'critical');
        $items = collect($data['items'])->map(fn ($item) => [
            'id' => $item['id'],
            'result' => $item['id'] === $critical['id'] ? 'ng' : 'ok',
            'note' => $item['id'] === $critical['id'] ? 'Đã báo điều phối đổi xe.' : null,
        ])->all();

        $this->postJson("/api/driver/checklists/{$data['id']}/submit", [
            'lock_version' => $data['lock_version'], 'confirm' => true, 'items' => $items,
        ])->assertOk()
            ->assertJsonPath('data.status', 'submitted')
            ->assertJsonPath('data.result', 'fail')
            ->assertJsonPath('data.critical_ng_count', 1);

        $this->assertDatabaseHas('checklist_submission_items', [
            'id' => $critical['id'], 'result' => 'ng', 'note' => 'Đã báo điều phối đổi xe.',
        ]);
        $this->assertSame(1, ChecklistSubmission::whereKey($data['id'])->count());
        foreach ([$this->dispatcher, $secondDispatcher] as $recipient) {
            $alerts = $recipient->notifications()->where('type', ChecklistCriticalFailedNotification::class)->get();
            $this->assertCount(1, $alerts);
            $this->assertSame($data['id'], $alerts->first()->data['submission_id']);
        }

        $this->getJson("/api/driver/checklists/{$data['id']}")->assertOk()
            ->assertJsonPath('data.status', 'submitted')
            ->assertJsonPath('data.result', 'fail');
    }

    public function test_optional_photo_can_still_be_uploaded_to_a_draft(): void
    {
        $data = $this->start();
        $item = $data['items'][0];
        $this->post("/api/driver/checklists/{$data['id']}/items/{$item['id']}/evidence", [
            'file' => UploadedFile::fake()->image('optional.jpg'),
        ], ['Accept' => 'application/json'])->assertCreated();
        $detail = $this->getJson("/api/driver/checklists/{$data['id']}")->assertOk();
        $saved = collect($detail->json('data.items'))->firstWhere('id', $item['id']);
        $this->assertCount(1, $saved['attachments']);
        $this->assertFalse($saved['requires_photo_on_ng']);
    }

    public function test_weekly_legacy_draft_accepts_ng_with_note_without_photo(): void
    {
        Notification::fake();
        $weekly = ChecklistTemplate::where('code', ChecklistTemplate::CODE_WEEKLY)->firstOrFail();
        $weekly->items()->update(['requires_photo_on_ng' => true]);
        $data = $this->actingAs($this->driverUser)->postJson('/api/driver/checklists', [
            'template_id' => $weekly->id, 'vehicle_id' => $this->vehicle->id,
        ])->assertCreated()->json('data');
        $critical = collect($data['items'])->firstWhere('severity', 'critical');
        $this->assertFalse($critical['requires_photo_on_ng']);
        ChecklistSubmissionItem::where('submission_id', $data['id'])->update(['requires_photo_on_ng' => true]);
        $items = collect($data['items'])->map(fn ($item) => [
            'id' => $item['id'],
            'result' => $item['id'] === $critical['id'] ? 'ng' : 'ok',
            'note' => $item['id'] === $critical['id'] ? 'Đã báo điều vận kiểm tra lại giấy tờ.' : null,
        ])->all();
        $this->postJson("/api/driver/checklists/{$data['id']}/submit", [
            'lock_version' => 0, 'confirm' => true, 'items' => $items,
        ])->assertOk()->assertJsonPath('data.status', 'submitted')->assertJsonPath('data.result', 'fail');
    }

    public function test_dispatcher_sees_list_compliance_and_can_review(): void
    {
        $data = $this->start();
        $id = $data['id'];
        $payload = collect($data['items'])->map(fn ($i) => ['id' => $i['id'], 'result' => $i['allow_na'] ? 'na' : 'ok'])->all();
        $this->postJson("/api/driver/checklists/{$id}/submit", ['lock_version' => $data['lock_version'], 'confirm' => true, 'items' => $payload])
            ->assertOk()
            ->assertJsonPath('data.result', 'pass');

        $this->actingAs($this->dispatcher);

        $list = $this->getJson('/api/checklists?result=pass')->assertOk();
        $this->assertSame([$id], collect($list->json('data.items'))->pluck('id')->all());

        $matrix = $this->getJson('/api/checklists/compliance?'.http_build_query([
            'template_id' => $this->dailyTemplate()->id,
            'date_from' => '2026-10-04',
            'date_to' => '2026-10-05',
        ]))->assertOk();
        $row = collect($matrix->json('data.rows'))->firstWhere('driver.id', $this->driver->id);
        // 04/10 là Chủ nhật tuần 40; 05/10 là thứ Hai tuần 41.
        $this->assertSame('todo', $row['cells']['2026-W40']['state']);
        $this->assertSame('pass', $row['cells']['2026-W41']['state']);

        $this->postJson("/api/checklists/{$id}/review", ['review_note' => 'OK'])
            ->assertOk()
            ->assertJsonPath('data.status', 'reviewed');

        $this->get("/api/checklists/{$id}/xlsx")->assertOk();
        $this->get('/api/checklists/export')->assertOk();
    }

    public function test_other_driver_cannot_view_or_edit(): void
    {
        $data = $this->start();

        $other = User::factory()->create(['is_active' => true]);
        $other->assignRole('driver');
        Driver::query()->create(['user_id' => $other->id, 'full_name' => 'TX khác', 'employment_status' => 'active', 'availability_status' => 'available']);

        $this->actingAs($other);
        $this->getJson("/api/driver/checklists/{$data['id']}")->assertForbidden();
        $this->patchJson("/api/driver/checklists/{$data['id']}", ['lock_version' => 0, 'note' => 'x'])->assertForbidden();
    }

    public function test_na_is_rejected_for_items_that_do_not_allow_it(): void
    {
        $data = $this->start();
        $brake = collect($data['items'])->firstWhere('title', 'Phanh');

        $this->patchJson("/api/driver/checklists/{$data['id']}", [
            'lock_version' => $data['lock_version'],
            'items' => [['id' => $brake['id'], 'result' => 'na']],
        ])->assertStatus(422);
    }

    public function test_reminder_command_notifies_pending_drivers_once(): void
    {
        Notification::fake();

        // 05:32 thứ Hai giờ VN → trong cửa sổ slot daily 05:30.
        $this->artisan('checklist:remind-drivers', ['--at' => '2026-10-05 05:32'])->assertSuccessful();
        Notification::assertSentToTimes($this->driverUser, \App\Notifications\DriverChecklistReminderNotification::class, 1);

        // Chạy lại cùng cửa sổ → không gửi trùng.
        $this->artisan('checklist:remind-drivers', ['--at' => '2026-10-05 05:34'])->assertSuccessful();
        Notification::assertSentToTimes($this->driverUser, \App\Notifications\DriverChecklistReminderNotification::class, 1);

        // Ngày kế tiếp cron trễ 30 phút vẫn bắt kịp slot chưa gửi của ngày đó.
        $this->artisan('checklist:remind-drivers', ['--at' => '2026-10-06 06:00'])->assertSuccessful();
        Notification::assertSentToTimes($this->driverUser, \App\Notifications\DriverChecklistReminderNotification::class, 2);
    }

    public function test_reminder_skips_driver_who_already_submitted_this_week(): void
    {
        Notification::fake();
        $submission = ChecklistSubmission::query()->create([
            'code' => 'CL-261006-9',
            'template_id' => $this->dailyTemplate()->id,
            'driver_id' => $this->driver->id,
            'user_id' => $this->driverUser->id,
            'vehicle_id' => $this->vehicle->id,
            'period_type' => 'weekly',
            'period_key' => '2026-W41',
            'status' => 'submitted',
            'result' => 'pass',
            'submitted_at' => now(),
        ]);
        $this->assertNotNull($submission->id);

        // Nộp thứ Hai → thứ Năm cùng tuần không nhắc nữa.
        $this->artisan('checklist:remind-drivers', ['--at' => '2026-10-08 05:31'])->assertSuccessful();
        Notification::assertNotSentTo($this->driverUser, \App\Notifications\DriverChecklistReminderNotification::class);
    }

    public function test_safety_checklist_submitted_any_day_covers_the_whole_week(): void
    {
        $this->assertSame('weekly', $this->dailyTemplate()->frequency);
        $this->assertNull($this->dailyTemplate()->reminderSlots()->value('escalate_after_minutes'));

        // Nộp thứ Tư 07/10.
        Carbon::setTestNow(Carbon::parse('2026-10-07 02:00:00', 'UTC'));
        $data = $this->start();
        $payload = collect($data['items'])->map(fn ($i) => ['id' => $i['id'], 'result' => $i['allow_na'] ? 'na' : 'ok'])->all();
        $this->postJson("/api/driver/checklists/{$data['id']}/submit", ['lock_version' => $data['lock_version'], 'confirm' => true, 'items' => $payload])
            ->assertOk()->assertJsonPath('data.period_key', '2026-W41');

        $state = fn () => collect($this->getJson('/api/driver/checklists/overview')->assertOk()->json('data.periods'))
            ->firstWhere('template.code', ChecklistTemplate::CODE_DAILY)['state'];

        // Thứ Hai và Chủ nhật cùng tuần đều tính là đã làm.
        Carbon::setTestNow(Carbon::parse('2026-10-05 00:10:00', 'UTC'));
        $this->assertSame('pass', $state());
        Carbon::setTestNow(Carbon::parse('2026-10-11 10:00:00', 'UTC'));
        $this->assertSame('pass', $state());

        // Sang tuần mới phải làm lại.
        Carbon::setTestNow(Carbon::parse('2026-10-12 00:10:00', 'UTC'));
        $this->assertSame('todo', $state());
    }

    public function test_failed_banner_clears_after_a_passing_resubmission_in_the_same_week(): void
    {
        Notification::fake();
        $submit = function (bool $fail): void {
            $data = $this->start();
            $critical = collect($data['items'])->firstWhere('severity', 'critical');
            $items = collect($data['items'])->map(fn ($i) => [
                'id' => $i['id'],
                'result' => $fail && $i['id'] === $critical['id'] ? 'ng' : ($i['allow_na'] ? 'na' : 'ok'),
                'note' => $fail && $i['id'] === $critical['id'] ? 'Hỏng' : null,
            ])->all();
            $this->postJson("/api/driver/checklists/{$data['id']}/submit", ['lock_version' => $data['lock_version'], 'confirm' => true, 'items' => $items])->assertOk();
        };

        $submit(true);
        $this->assertCount(1, $this->getJson('/api/driver/checklists/overview')->json('data.failed_today'));

        Carbon::setTestNow(Carbon::parse('2026-10-07 02:00:00', 'UTC'));
        $submit(false);
        $this->assertSame([], $this->getJson('/api/driver/checklists/overview')->json('data.failed_today'));
    }
}
