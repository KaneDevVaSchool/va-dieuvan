<?php

namespace Tests\Feature;

use App\Models\AcademicTerm;
use App\Models\Campus;
use App\Models\Driver;
use App\Models\P2pPolicyTerm;
use App\Models\PolicyRoute;
use App\Models\PolicyStudent;
use App\Models\PolicyTripSlot;
use App\Models\Student;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\P2pPolicy\P2pPolicyTripMaterializer;
use App\Support\P2pPolicy;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class P2pPolicyTripSlotsIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{viewer: User, term: P2pPolicyTerm, route: PolicyRoute}
     */
    private function seedWithSlots(): array
    {
        $this->seed(RbacSeeder::class);

        $viewer = User::factory()->create(['is_active' => true]);
        $viewer->assignRole('dispatcher');

        $activator = User::factory()->create(['is_active' => true]);
        $activator->assignRole('dispatcher');

        $origin = Campus::create(['code' => 'A', 'name' => 'Campus A', 'is_active' => true]);
        $dest = Campus::create(['code' => 'B', 'name' => 'Campus B', 'is_active' => true]);

        $academicTerm = AcademicTerm::create([
            'academic_year' => '2025-2026',
            'term_code' => 'HK1',
            'name' => 'HK1',
            'starts_on' => '2025-08-01',
            'ends_on' => '2026-01-01',
            'is_active' => true,
        ]);

        $term = P2pPolicyTerm::create([
            'academic_term_id' => $academicTerm->id,
            'operating_from' => '2025-08-11',
            'operating_to' => '2025-08-12',
            'weekdays_mask' => 31,
            'default_morning_start' => '06:00:00',
            'default_morning_end' => '07:00:00',
            'status' => 'active',
        ]);

        $vehicle = Vehicle::create(['license_plate' => '51A-IDX', 'status' => 'ready']);
        $driver = Driver::create(['full_name' => 'TX Index', 'employment_status' => 'active']);

        $route = PolicyRoute::create([
            'p2p_policy_term_id' => $term->id,
            'name' => 'Route Index',
            'origin_campus_id' => $origin->id,
            'dest_campus_id' => $dest->id,
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'is_active' => true,
        ]);

        $student = Student::create(['student_code' => 'HS-IDX', 'full_name' => 'HS', 'grade' => '6A']);
        PolicyStudent::create([
            'policy_route_id' => $route->id,
            'student_id' => $student->id,
            'student_code' => 'HS-IDX',
            'student_name' => 'HS',
            'class_name' => '6A',
            'direction' => 'two_way',
            'policy_type' => 'internal',
            'effective_from' => '2025-08-01',
            'is_active' => true,
        ]);

        $materializer = app(P2pPolicyTripMaterializer::class);
        $materializer->materializeSlot($term, $route, Carbon::parse('2025-08-11'), P2pPolicy::LEG_MORNING, $activator->id);
        $materializer->materializeSlot($term, $route, Carbon::parse('2025-08-12'), P2pPolicy::LEG_MORNING, $activator->id);

        return compact('viewer', 'term', 'route');
    }

    public function test_index_requires_permission(): void
    {
        $this->seed(RbacSeeder::class);
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user)->getJson('/api/p2p-policy/trip-slots')->assertForbidden();
    }

    public function test_index_filters_by_term_and_paginates(): void
    {
        $fx = $this->seedWithSlots();
        $this->assertSame(2, PolicyTripSlot::query()->count());

        $res = $this->actingAs($fx['viewer'])->getJson('/api/p2p-policy/trip-slots?'.http_build_query([
            'p2p_policy_term_id' => $fx['term']->id,
            'per_page' => 1,
            'page' => 1,
        ]));

        $res->assertOk();
        $res->assertJsonPath('data.meta.per_page', 1);
        $res->assertJsonPath('data.meta.total', 2);
        $res->assertJsonCount(1, 'data.items');
        $this->assertArrayHasKey('run_date', $res->json('data.items.0'));
        $this->assertArrayHasKey('trip_id', $res->json('data.items.0'));
        $this->assertSame('Route Index', $res->json('data.items.0.route_name'));

        $page2 = $this->actingAs($fx['viewer'])->getJson('/api/p2p-policy/trip-slots?'.http_build_query([
            'p2p_policy_term_id' => $fx['term']->id,
            'per_page' => 1,
            'page' => 2,
        ]));
        $page2->assertOk();
        $page2->assertJsonCount(1, 'data.items');
        $this->assertNotSame(
            $res->json('data.items.0.id'),
            $page2->json('data.items.0.id'),
        );
    }
}
