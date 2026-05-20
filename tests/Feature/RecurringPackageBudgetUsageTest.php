<?php

namespace Tests\Feature;

use App\Models\DispatchPackage;
use App\Models\Role;
use App\Models\DispatchRequest;
use App\Models\DispatchRequestTemplate;
use App\Models\User;
use App\Services\RecurringDispatch\RecurringPackageBudgetService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecurringPackageBudgetUsageTest extends TestCase
{
    use RefreshDatabase;

    public function test_package_budget_usage_sums_service_price_on_instances(): void
    {
        $this->seed(RbacSeeder::class);

        Role::query()->firstOrCreate(
            ['name' => 'portal_club_coordinator', 'guard_name' => 'web'],
            ['display_name' => 'Điều phối CLB (portal)'],
        );

        $requester = User::factory()->create(['is_active' => true]);
        $requester->assignRole('portal_club_coordinator');

        $pkg = DispatchPackage::create([
            'trip_type' => 'point_to_point',
            'label' => 'CLB A',
            'monthly_budget' => 10_000_000,
            'total_sessions' => 5,
            'sessions_used' => 0,
        ]);

        $template = DispatchRequestTemplate::create([
            'requester_id' => $requester->id,
            'dispatch_package_id' => $pkg->id,
            'is_active' => true,
            'trip_type' => 'point_to_point',
            'origin' => 'A',
            'destination' => 'B',
            'recurrence_rule' => ['freq' => 'weekly', 'interval' => 1, 'byweekday' => [1]],
            'recurrence_time' => '08:00:00',
            'start_date' => now()->toDateString(),
            'recurrence_end_date' => now()->addDays(7)->toDateString(),
            'wizard_snapshot' => ['form' => ['point_purpose_kind' => 'extracurricular', 'estimated_vehicle_cost' => '10000000']],
        ]);

        DispatchRequest::create([
            'requester_id' => $requester->id,
            'dispatch_request_template_id' => $template->id,
            'trip_type' => 'point_to_point',
            'origin' => 'A',
            'destination' => 'B',
            'depart_at' => now()->addDays(2),
            'status' => 'price_filled',
            'source_channel' => 'portal',
            'service_price' => 4_000_000,
            'wizard_snapshot' => ['form' => ['point_purpose_kind' => 'extracurricular']],
        ]);

        DispatchRequest::create([
            'requester_id' => $requester->id,
            'dispatch_request_template_id' => $template->id,
            'trip_type' => 'point_to_point',
            'origin' => 'A',
            'destination' => 'B',
            'depart_at' => now()->addDays(3),
            'status' => 'price_filled',
            'source_channel' => 'portal',
            'service_price' => 5_000_000,
            'wizard_snapshot' => ['form' => ['point_purpose_kind' => 'extracurricular']],
        ]);

        $usage = app(RecurringPackageBudgetService::class)->summarize($pkg->fresh());

        $this->assertNotNull($usage);
        $this->assertSame(10_000_000.0, $usage['budget']);
        $this->assertSame(9_000_000.0, $usage['used']);
        $this->assertSame(1_000_000.0, $usage['remaining']);
        $this->assertSame('warning', $usage['severity']);

        $this->actingAs($requester);
        $list = $this->getJson('/api/portal/dispatch-requests?extracurricular_only=1&per_page=20');
        $list->assertOk();
        $first = collect($list->json('data.items'))->first();
        $this->assertNotNull($first['dispatch_package_budget_usage'] ?? null);
        $this->assertSame(1_000_000.0, (float) $first['dispatch_package_budget_usage']['remaining']);
    }
}
