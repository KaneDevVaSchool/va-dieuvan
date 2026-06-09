<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\DispatchRequest;
use App\Models\User;
use App\Notifications\DeptApprovalReminderNotification;
use App\Notifications\DeptHeadApprovalRequestedNotification;
use App\Notifications\DeptHeadDecisionNotification;
use App\Notifications\SignedPaperUploadReminderNotification;
use App\Notifications\NewDispatchRequestNotification;
use App\Notifications\TripAssignedNotification;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DispatchRequestFillPriceDeptHeadTest extends TestCase
{
    use RefreshDatabase;

    public function test_fill_price_requires_dept_head_choice_when_role_exists(): void
    {
        $this->seed(RbacSeeder::class);

        User::factory()->create(['is_active' => true])->assignRole('department_head');

        $dispatcher = User::factory()->create(['is_active' => true]);
        $dispatcher->assignRole('dispatcher');

        $requester = User::factory()->create(['department_id' => null, 'is_active' => true]);
        $requester->assignRole('internal_user');

        $dr = DispatchRequest::create([
            'requester_id' => $requester->id,
            'trip_type' => 'business',
            'origin' => 'A',
            'destination' => 'B',
            'depart_at' => now()->addDays(3),
            'arrive_by' => now()->addDays(4),
            'status' => 'pending',
            'source_channel' => 'portal',
            'is_urgent' => false,
            'paper_status' => 'pending',
            'wizard_snapshot' => [],
        ]);

        $this->actingAs($dispatcher);

        $this->patchJson("/api/dispatch-requests/{$dr->id}/fill-price", [
            'service_price' => 100000,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['assigned_dept_head_id']);
    }

    public function test_fill_price_uses_preset_assigned_dept_head_without_payload_user_id(): void
    {
        $this->seed(RbacSeeder::class);

        $dispatcher = User::factory()->create(['is_active' => true]);
        $dispatcher->assignRole('dispatcher');

        $requester = User::factory()->create(['is_active' => true]);
        $requester->assignRole('internal_user');

        $head = User::factory()->create(['is_active' => true, 'email' => 'preset.head@example.test']);
        $head->assignRole('department_head');

        $dr = DispatchRequest::create([
            'requester_id' => $requester->id,
            'trip_type' => 'business',
            'origin' => 'A',
            'destination' => 'B',
            'depart_at' => now()->addDays(5),
            'status' => 'pending',
            'source_channel' => 'portal',
            'assigned_dept_head_id' => $head->id,
            'is_urgent' => false,
            'paper_status' => 'pending',
            'wizard_snapshot' => [],
        ]);

        Notification::fake();

        $this->actingAs($dispatcher);

        $this->patchJson("/api/dispatch-requests/{$dr->id}/fill-price", [
            'service_price' => 500000,
            'rows' => [],
        ])
            ->assertSuccessful();

        Notification::assertNotSentTo($head, DeptHeadApprovalRequestedNotification::class);

        $fresh = DispatchRequest::query()->findOrFail($dr->id);
        $this->assertSame('approved', $fresh->status);
        $this->assertSame($head->id, $fresh->assigned_dept_head_id);
        $this->assertNotNull($fresh->approved_by);
    }

    public function test_fill_price_dispatches_notification_only_to_assigned_department_head(): void
    {
        $this->seed(RbacSeeder::class);

        $dept = Department::query()->create(['name' => 'Phòng QC', 'code' => 'QC']);

        $dispatcher = User::factory()->create(['is_active' => true]);
        $dispatcher->assignRole('dispatcher');

        $requester = User::factory()->create([
            'department_id' => $dept->id,
            'is_active' => true,
        ]);
        $requester->assignRole('internal_user');

        $headChosen = User::factory()->create([
            'department_id' => $dept->id,
            'is_active' => true,
            'email' => 'chosen.head@example.test',
        ]);
        $headChosen->assignRole('department_head');

        $headOther = User::factory()->create([
            'department_id' => $dept->id,
            'is_active' => true,
            'email' => 'other.head@example.test',
        ]);
        $headOther->assignRole('department_head');

        $dr = DispatchRequest::create([
            'requester_id' => $requester->id,
            'trip_type' => 'business',
            'origin' => 'A',
            'destination' => 'B',
            'depart_at' => now()->addDays(3),
            'status' => 'pending',
            'source_channel' => 'portal',
            'is_urgent' => false,
            'paper_status' => 'pending',
            'assigned_dept_head_id' => $headChosen->id,
            'wizard_snapshot' => [
                'form' => ['purpose' => 'Công tác', 'coordinator_name' => 'Lê Minh'],
                'businessRows' => [['extra_fee' => 35000]],
            ],
        ]);

        Notification::fake();

        $this->actingAs($dispatcher);

        $this->patchJson("/api/dispatch-requests/{$dr->id}/fill-price", [
            'service_price' => 240000,
            'rows' => [],
        ])
            ->assertSuccessful();

        Notification::assertNotSentTo($headChosen, DeptHeadApprovalRequestedNotification::class);
        Notification::assertNotSentTo($headOther, DeptHeadApprovalRequestedNotification::class);

        $fresh = DispatchRequest::query()->findOrFail($dr->id);
        $this->assertSame('approved', $fresh->status);
        $this->assertSame($headChosen->id, $fresh->assigned_dept_head_id);
    }

    public function test_dept_head_approval_mail_grand_total_matches_service_price_without_double_counting_extra(): void
    {
        $this->seed(RbacSeeder::class);

        $head = User::factory()->create(['is_active' => true, 'email' => 'head@example.test']);
        $head->assignRole('department_head');

        $dr = DispatchRequest::create([
            'requester_id' => User::factory()->create(['is_active' => true])->id,
            'trip_type' => 'business',
            'origin' => 'A',
            'destination' => 'B',
            'depart_at' => now()->addDays(3),
            'status' => 'price_filled',
            'source_channel' => 'portal',
            'is_urgent' => false,
            'paper_status' => 'pending',
            'service_price' => 120000,
            'assigned_dept_head_id' => $head->id,
            'wizard_snapshot' => [
                'businessRows' => [
                    ['unit_price' => 100000, 'extra_fee' => 20000],
                ],
            ],
        ]);

        $notification = new DeptHeadApprovalRequestedNotification($dr->id);
        $mail = $notification->toMail($head);
        $html = (string) (method_exists($mail, 'render') ? $mail->render() : '');

        $this->assertStringContainsString('120.000', $html);
        $this->assertStringNotContainsString('140.000', $html);
        $this->assertStringContainsString('Tổng phụ thu', $html);
    }

    public function test_fill_price_accepts_head_from_any_department_even_if_requester_has_no_department(): void
    {
        $this->seed(RbacSeeder::class);

        $deptB = Department::query()->create(['name' => 'Phòng B', 'code' => 'PHB']);

        $dispatcher = User::factory()->create(['is_active' => true]);
        $dispatcher->assignRole('dispatcher');

        $requester = User::factory()->create(['department_id' => null, 'is_active' => true]);
        $requester->assignRole('internal_user');

        $headB = User::factory()->create(['department_id' => $deptB->id, 'is_active' => true]);
        $headB->assignRole('department_head');

        $dr = DispatchRequest::create([
            'requester_id' => $requester->id,
            'trip_type' => 'business',
            'origin' => 'A',
            'destination' => 'B',
            'depart_at' => now()->addDays(3),
            'arrive_by' => now()->addDays(4),
            'status' => 'pending',
            'source_channel' => 'portal',
            'is_urgent' => false,
            'paper_status' => 'pending',
            'assigned_dept_head_id' => $headB->id,
            'wizard_snapshot' => [],
        ]);

        $this->actingAs($dispatcher);

        $this->patchJson("/api/dispatch-requests/{$dr->id}/fill-price", [
            'service_price' => 100000,
            'rows' => [],
        ])
            ->assertSuccessful();

        $fresh = DispatchRequest::query()->findOrFail($dr->id);
        $this->assertSame('approved', $fresh->status);
        $this->assertSame($headB->id, $fresh->assigned_dept_head_id);
    }

    public function test_fill_price_rejects_user_who_is_not_active_department_head(): void
    {
        $this->seed(RbacSeeder::class);

        $dept = Department::query()->create(['name' => 'Phòng QA', 'code' => 'QA']);

        $dispatcher = User::factory()->create(['is_active' => true]);
        $dispatcher->assignRole('dispatcher');

        $requester = User::factory()->create(['department_id' => $dept->id, 'is_active' => true]);
        $requester->assignRole('internal_user');

        $internalOnly = User::factory()->create(['department_id' => $dept->id, 'is_active' => true]);
        $internalOnly->assignRole('internal_user');

        User::factory()->create(['is_active' => true])->assignRole('department_head');

        $dr = DispatchRequest::create([
            'requester_id' => $requester->id,
            'trip_type' => 'business',
            'origin' => 'A',
            'destination' => 'B',
            'depart_at' => now()->addDays(3),
            'arrive_by' => now()->addDays(4),
            'status' => 'pending',
            'source_channel' => 'portal',
            'is_urgent' => false,
            'paper_status' => 'pending',
            'assigned_dept_head_id' => $internalOnly->id,
            'wizard_snapshot' => [],
        ]);

        $this->actingAs($dispatcher);

        $this->patchJson("/api/dispatch-requests/{$dr->id}/fill-price", [
            'service_price' => 100000,
            'rows' => [],
        ])
            ->assertStatus(422);
    }

    public function test_fill_price_prohibits_dept_head_user_id_in_payload(): void
    {
        $this->seed(RbacSeeder::class);

        User::factory()->create(['is_active' => true])->assignRole('department_head');

        $dispatcher = User::factory()->create(['is_active' => true]);
        $dispatcher->assignRole('dispatcher');

        $head = User::factory()->create(['is_active' => true]);
        $head->assignRole('department_head');

        $dr = DispatchRequest::create([
            'requester_id' => User::factory()->create(['is_active' => true])->id,
            'trip_type' => 'business',
            'origin' => 'A',
            'destination' => 'B',
            'depart_at' => now()->addDays(3),
            'status' => 'pending',
            'source_channel' => 'portal',
            'is_urgent' => false,
            'paper_status' => 'pending',
            'assigned_dept_head_id' => $head->id,
            'wizard_snapshot' => [],
        ]);

        $this->actingAs($dispatcher);

        $this->patchJson("/api/dispatch-requests/{$dr->id}/fill-price", [
            'service_price' => 100000,
            'dept_head_user_id' => $head->id,
            'rows' => [],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['dept_head_user_id']);
    }

    public function test_assigned_dept_head_can_approve_even_when_requester_has_no_department(): void
    {
        $this->seed(RbacSeeder::class);

        $deptB = Department::query()->create(['name' => 'Phòng B', 'code' => 'PHB']);

        $requester = User::factory()->create(['department_id' => null, 'is_active' => true]);
        $requester->assignRole('internal_user');

        $headB = User::factory()->create(['department_id' => $deptB->id, 'is_active' => true]);
        $headB->assignRole('department_head');

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
            'service_price' => 150000,
            'assigned_dept_head_id' => $headB->id,
            'wizard_snapshot' => [],
        ]);

        $this->actingAs($headB);

        $this->postJson("/api/dispatch-requests/{$dr->id}/dept-decision", [
            'decision' => 'approve',
        ], ['Idempotency-Key' => 'test-dept-approve-'.$dr->id])
            ->assertSuccessful();

        $this->assertSame('approved', DispatchRequest::query()->findOrFail($dr->id)->status);
    }

    public function test_dept_decision_notifies_requester_on_approve(): void
    {
        $this->seed(RbacSeeder::class);

        $dept = Department::query()->create(['name' => 'Phòng A', 'code' => 'PHA']);

        $requester = User::factory()->create([
            'department_id' => $dept->id,
            'is_active' => true,
            'email' => 'requester@example.test',
        ]);
        $requester->assignRole('internal_user');

        $head = User::factory()->create([
            'department_id' => $dept->id,
            'is_active' => true,
            'email' => 'head@example.test',
            'name' => 'Trưởng Test',
        ]);
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
            'service_price' => 200000,
            'assigned_dept_head_id' => $head->id,
            'wizard_snapshot' => [],
        ]);

        Notification::fake();

        $this->actingAs($head);

        $this->postJson("/api/dispatch-requests/{$dr->id}/dept-decision", [
            'decision' => 'approve',
        ], ['Idempotency-Key' => 'dept-decision-approve-'.$dr->id])
            ->assertSuccessful();

        Notification::assertSentTo($requester, DeptHeadDecisionNotification::class, function (DeptHeadDecisionNotification $n) use ($dr): bool {
            return $n->dispatchRequestId === $dr->id && $n->decision === 'approve';
        });

        Notification::assertSentTo($requester, SignedPaperUploadReminderNotification::class, function (SignedPaperUploadReminderNotification $n) use ($dr): bool {
            return $n->dispatchRequestId === $dr->id;
        });

        $fresh = DispatchRequest::query()->findOrFail($dr->id);
        $mail = (new DeptHeadDecisionNotification($fresh->id, 'approve'))->toMail($requester);
        $html = (string) (method_exists($mail, 'render') ? $mail->render() : '');

        $this->assertStringContainsString('Phiếu đề xuất đã được duyệt', $mail->subject);
        $this->assertStringContainsString('/requests/'.$fresh->id, $html);
    }

    public function test_dept_decision_notifies_requester_on_reject_with_mail_body(): void
    {
        $this->seed(RbacSeeder::class);

        $dept = Department::query()->create(['name' => 'Phòng B', 'code' => 'PHB']);

        $requester = User::factory()->create([
            'department_id' => $dept->id,
            'is_active' => true,
            'email' => 'requester.reject@example.test',
        ]);
        $requester->assignRole('internal_user');

        $head = User::factory()->create(['department_id' => $dept->id, 'is_active' => true]);
        $head->assignRole('department_head');

        $dr = DispatchRequest::create([
            'requester_id' => $requester->id,
            'trip_type' => 'business',
            'origin' => 'A',
            'destination' => 'B',
            'depart_at' => now()->addDays(5),
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
            'decision' => 'reject',
            'rejection_reason' => 'Không đủ ngân sách',
        ], ['Idempotency-Key' => 'dept-decision-reject-'.$dr->id])
            ->assertSuccessful();

        Notification::assertSentTo($requester, DeptHeadDecisionNotification::class, function (DeptHeadDecisionNotification $n) use ($dr): bool {
            return $n->dispatchRequestId === $dr->id && $n->decision === 'reject';
        });

        $fresh = DispatchRequest::query()->findOrFail($dr->id);
        $mail = (new DeptHeadDecisionNotification($fresh->id, 'reject'))->toMail($requester);
        $html = (string) (method_exists($mail, 'render') ? $mail->render() : '');

        $this->assertStringContainsString('Phiếu đề xuất bị từ chối', $mail->subject);
        $this->assertStringContainsString('Không đủ ngân sách', $html);
    }

    public function test_all_dispatch_mail_views_render_without_blade_errors(): void
    {
        $this->seed(RbacSeeder::class);

        $head = User::factory()->create(['is_active' => true, 'email' => 'head@example.test', 'name' => 'Head']);
        $head->assignRole('department_head');

        $requester = User::factory()->create(['is_active' => true, 'email' => 'req@example.test', 'name' => 'Requester']);
        $requester->assignRole('internal_user');

        $driver = User::factory()->create(['is_active' => true, 'email' => 'driver@example.test', 'name' => 'Driver']);

        $dr = DispatchRequest::create([
            'requester_id' => $requester->id,
            'trip_type' => 'business',
            'origin' => 'A',
            'destination' => 'B',
            'depart_at' => now()->addDays(2),
            'arrive_by' => now()->addDays(2)->addHours(4),
            'status' => 'price_filled',
            'source_channel' => 'portal',
            'is_urgent' => true,
            'paper_status' => 'pending',
            'service_price' => 120000,
            'price_filled_at' => now()->subHours(30),
            'assigned_dept_head_id' => $head->id,
            'approved_by' => $head->id,
            'wizard_snapshot' => [
                'form' => ['purpose' => 'Test', 'coordinator_name' => 'Coord'],
                'businessRows' => [['unit_price' => 100000, 'extra_fee' => 20000]],
            ],
        ]);

        $mails = [
            (new DeptHeadApprovalRequestedNotification($dr->id))->toMail($head),
            (new DeptHeadDecisionNotification($dr->id, 'approve'))->toMail($requester),
            (new DeptHeadDecisionNotification($dr->id, 'reject'))->toMail($requester),
            (new NewDispatchRequestNotification($dr->id, 'A → B', true))->toMail($head),
            (new TripAssignedNotification(
                tripId: 99,
                tripType: 'business',
                origin: 'A',
                destination: 'B',
                departAt: now()->addDay()->toIso8601String(),
                isUrgent: true,
            ))->toMail($driver),
            (new DeptApprovalReminderNotification($dr->id))->toMail($head),
        ];

        foreach ($mails as $mail) {
            $html = (string) (method_exists($mail, 'render') ? $mail->render() : '');
            $this->assertNotSame('', trim($html));
        }
    }
}
