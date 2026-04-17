<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_summary_returns_200_with_joined_cost_query(): void
    {
        $this->seed(RbacSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('dispatcher');

        $this->actingAs($user);

        $this->getJson('/api/reports/summary')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'range',
                    'trips_by_status',
                    'trips_by_hour',
                    'trips_by_trip_type',
                    'trips_by_fleet_mode',
                    'trips_by_license_plate',
                    'trip_completion',
                    'trip_records_distance_km',
                    'top_requesters',
                    'dispatch_requests_by_status',
                    'costs_by_pipeline_status',
                    'confirmed_costs_by_type',
                    'confirmed_costs_by_provider',
                    'cargo_sla_breaches',
                    'vehicle_compliance' => [
                        'inspection' => ['overdue', 'due_within_30_days'],
                        'insurance' => ['overdue', 'due_within_30_days'],
                        'road_fee' => ['overdue', 'due_within_30_days'],
                        'maintenance' => ['no_recent_service_180d'],
                    ],
                ],
            ]);
    }
}
