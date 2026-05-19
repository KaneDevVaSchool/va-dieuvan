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

    public function test_fill_price_requires_dept_head_when_requester_has_department(): void
    {
        $this->seed(RbacSeeder::class);

        $dept = Department::query()->create(['name' => 'Phòng QA', 'code' => 'QA']);

        $dispatcher = User::factory()->create(['is_active' => true]);
        $dispatcher->assignRole('dispatcher');

        $requester = User::factory()->create(['department_id' => $dept->id, 'is_active' => true]);
        $requester->assignRole('internal_user');

        User::factory()->create(['department_id' => $dept->id, 'is_active' => true])->assignRole('department_head');

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
}
