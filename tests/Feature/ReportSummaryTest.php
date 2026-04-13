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
                    'confirmed_costs_by_type',
                    'confirmed_costs_by_provider',
                    'cargo_sla_breaches',
                ],
            ]);
    }
}
