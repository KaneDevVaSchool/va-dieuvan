<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverFrequencyReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_frequency_report_returns_200(): void
    {
        $this->seed(RbacSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('dispatcher');

        $this->actingAs($user);

        $this->getJson('/api/reports/driver-frequency?year='.now()->year)
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'year',
                    'kpi' => [
                        'totalTrips',
                        'activeDrivers',
                        'overallOnTime',
                    ],
                    'drivers',
                    'vehicles',
                    'quarterly',
                    'monthly',
                    'filter_options',
                    'year_comparison',
                ],
            ]);
    }
}
