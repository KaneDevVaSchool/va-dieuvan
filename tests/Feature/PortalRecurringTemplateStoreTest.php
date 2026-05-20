<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PortalRecurringTemplateStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_portal_store_extracurricular_recurring_skips_two_hour_depart_rule(): void
    {
        $this->seed(RbacSeeder::class);
        $tz = config('app.timezone') ?: 'UTC';
        Carbon::setTestNow(Carbon::parse('2026-05-20 14:00:00', $tz));

        $requester = User::factory()->create(['is_active' => true]);
        $requester->assignRole('internal_user');

        $this->actingAs($requester);

        $payload = [
            'trip_type' => 'point_to_point',
            'source_channel' => 'portal',
            'origin' => 'Đón A',
            'destination' => 'Trả B',
            'depart_at' => Carbon::parse('2026-05-20 07:00:00', $tz)->toIso8601String(),
            'start_date' => '2026-05-20',
            'return_time' => '17:00',
            'recurrence_rule' => [
                'freq' => 'weekly',
                'interval' => 1,
                'byweekday' => [1, 2, 3, 4, 5],
            ],
            'recurrence_end_date' => '2026-05-26',
            'plan_label' => 'CLB Test T5',
            'wizard_snapshot' => [
                'form' => [
                    'point_purpose_kind' => 'extracurricular',
                    'trip_type' => 'point_to_point',
                    'plan_name' => 'CLB Test T5',
                ],
                'passengerRows' => [['pickup' => 'Đón A', 'dropoff' => 'Trả B']],
            ],
        ];

        $response = $this->postJson('/api/portal/dispatch-request-templates', $payload);
        if ($response->status() !== 201) {
            $this->fail((string) json_encode($response->json(), JSON_UNESCAPED_UNICODE));
        }
        $response->assertCreated();
    }
}
