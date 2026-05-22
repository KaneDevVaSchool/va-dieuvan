<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\DispatchRequest;
use App\Models\User;
use App\Notifications\SignedPaperUploadReminderNotification;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SignedPaperUploadReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_sends_reminder_when_approved_without_signed_paper(): void
    {
        $this->seed(RbacSeeder::class);
        Notification::fake();

        $requester = User::factory()->create([
            'is_active' => true,
            'email' => 'requester@example.test',
        ]);

        $dr = DispatchRequest::create([
            'requester_id' => $requester->id,
            'trip_type' => 'business',
            'origin' => 'A',
            'destination' => 'B',
            'depart_at' => now()->addDays(5),
            'arrive_by' => now()->addDays(5)->addHours(2),
            'status' => 'approved',
            'source_channel' => 'portal',
            'is_urgent' => false,
            'paper_status' => 'pending',
            'wizard_snapshot' => [],
        ]);
        $dr->forceFill(['updated_at' => now()->subHours(30)])->saveQuietly();

        Artisan::call('dispatch:remind-signed-paper-upload');

        Notification::assertSentTo(
            $requester,
            SignedPaperUploadReminderNotification::class,
            fn (SignedPaperUploadReminderNotification $n) => $n->dispatchRequestId === $dr->id,
        );
    }

    public function test_command_skips_when_signed_paper_exists(): void
    {
        $this->seed(RbacSeeder::class);
        Notification::fake();

        $requester = User::factory()->create(['is_active' => true]);

        $dr = DispatchRequest::create([
            'requester_id' => $requester->id,
            'trip_type' => 'business',
            'origin' => 'A',
            'destination' => 'B',
            'depart_at' => now()->addDays(5),
            'status' => 'approved',
            'source_channel' => 'portal',
            'is_urgent' => false,
            'paper_status' => 'pending',
            'wizard_snapshot' => [],
        ]);
        $dr->forceFill(['updated_at' => now()->subHours(30)])->saveQuietly();

        Attachment::create([
            'uploaded_by' => $requester->id,
            'attachable_type' => $dr->getMorphClass(),
            'attachable_id' => $dr->id,
            'kind' => 'signed_paper',
            'disk' => 'public',
            'path' => 'test/signed.pdf',
            'original_name' => 'signed.pdf',
            'size_bytes' => 100,
            'mime_type' => 'application/pdf',
        ]);

        Artisan::call('dispatch:remind-signed-paper-upload');

        Notification::assertNothingSent();
    }

    public function test_command_respects_daily_cooldown(): void
    {
        $this->seed(RbacSeeder::class);

        $requester = User::factory()->create(['is_active' => true]);

        $dr = DispatchRequest::create([
            'requester_id' => $requester->id,
            'trip_type' => 'business',
            'origin' => 'A',
            'destination' => 'B',
            'depart_at' => now()->addDays(5),
            'status' => 'approved',
            'source_channel' => 'portal',
            'is_urgent' => false,
            'paper_status' => 'pending',
            'wizard_snapshot' => [],
        ]);
        $dr->forceFill(['updated_at' => now()->subHours(30)])->saveQuietly();

        Artisan::call('dispatch:remind-signed-paper-upload');
        $this->assertSame(1, $requester->notifications()->count());

        Artisan::call('dispatch:remind-signed-paper-upload');
        $this->assertSame(1, $requester->notifications()->count());
    }

    public function test_immediate_reminder_on_dept_approve_without_signed_paper(): void
    {
        $this->seed(RbacSeeder::class);

        $dept = \App\Models\Department::query()->create(['name' => 'Phòng T', 'code' => 'PHT']);

        $requester = User::factory()->create(['department_id' => $dept->id, 'is_active' => true]);
        $requester->assignRole('internal_user');

        $head = User::factory()->create(['department_id' => $dept->id, 'is_active' => true]);
        $head->assignRole('department_head');

        $dr = DispatchRequest::create([
            'requester_id' => $requester->id,
            'trip_type' => 'business',
            'origin' => 'A',
            'destination' => 'B',
            'depart_at' => now()->addDays(3),
            'status' => 'price_filled',
            'source_channel' => 'portal',
            'is_urgent' => false,
            'paper_status' => 'pending',
            'service_price' => 100000,
            'assigned_dept_head_id' => $head->id,
            'wizard_snapshot' => [],
        ]);

        Notification::fake();

        $this->actingAs($head);
        $this->postJson("/api/dispatch-requests/{$dr->id}/dept-decision", [
            'decision' => 'approve',
        ], ['Idempotency-Key' => 'signed-paper-immediate-'.$dr->id])
            ->assertSuccessful();

        Notification::assertSentTo($requester, SignedPaperUploadReminderNotification::class);
    }

    public function test_reminder_mail_renders(): void
    {
        $this->seed(RbacSeeder::class);

        $requester = User::factory()->create([
            'email' => 'requester@example.test',
            'name' => 'Người đề xuất',
        ]);

        $dr = DispatchRequest::create([
            'requester_id' => $requester->id,
            'trip_type' => 'business',
            'origin' => 'A',
            'destination' => 'B',
            'depart_at' => now()->addDays(3),
            'status' => 'approved',
            'source_channel' => 'portal',
            'is_urgent' => false,
            'paper_status' => 'pending',
            'wizard_snapshot' => ['form' => ['purpose' => 'Test']],
        ]);

        $html = (string) (new SignedPaperUploadReminderNotification($dr->id))->toMail($requester)->render();
        $this->assertStringContainsString('scan phiếu', $html);
        $this->assertStringContainsString('OCR', $html);
    }
}
