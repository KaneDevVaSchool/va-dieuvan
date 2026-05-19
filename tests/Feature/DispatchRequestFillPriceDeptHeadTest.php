<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\DispatchRequest;
use App\Models\User;
use App\Notifications\DeptHeadApprovalRequestedNotification;
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
            ->assertJsonValidationErrors(['dept_head_user_id']);
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
            'wizard_snapshot' => [
                'form' => ['purpose' => 'Công tác', 'coordinator_name' => 'Lê Minh'],
                'businessRows' => [['extra_fee' => 35000]],
            ],
        ]);

        Notification::fake();

        $this->actingAs($dispatcher);

        $this->patchJson("/api/dispatch-requests/{$dr->id}/fill-price", [
            'service_price' => 240000,
            'dept_head_user_id' => $headChosen->id,
            'rows' => [],
        ])
            ->assertSuccessful();

        Notification::assertSentTo($headChosen, DeptHeadApprovalRequestedNotification::class);
        Notification::assertNotSentTo($headOther, DeptHeadApprovalRequestedNotification::class);

        $fresh = DispatchRequest::query()->findOrFail($dr->id);
        $this->assertSame('price_filled', $fresh->status);
        $this->assertSame($headChosen->id, $fresh->assigned_dept_head_id);

        $notification = new DeptHeadApprovalRequestedNotification($fresh->id);
        $mail = $notification->toMail($headChosen);
        $html = method_exists($mail, 'render') ? $mail->render() : '';

        $this->assertNotSame('', trim((string) $html));
        $this->assertStringContainsString('/dept/requests/'.$fresh->id, (string) $html);
        $this->assertStringContainsString('Phiếu đề xuất chờ duyệt —', $mail->subject);
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
            'wizard_snapshot' => [],
        ]);

        $this->actingAs($dispatcher);

        $this->patchJson("/api/dispatch-requests/{$dr->id}/fill-price", [
            'service_price' => 100000,
            'dept_head_user_id' => $headB->id,
            'rows' => [],
        ])
            ->assertSuccessful();

        $fresh = DispatchRequest::query()->findOrFail($dr->id);
        $this->assertSame('price_filled', $fresh->status);
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
            'wizard_snapshot' => [],
        ]);

        $this->actingAs($dispatcher);

        $this->patchJson("/api/dispatch-requests/{$dr->id}/fill-price", [
            'service_price' => 100000,
            'dept_head_user_id' => $internalOnly->id,
            'rows' => [],
        ])
            ->assertStatus(422);
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
}
