<?php

namespace Tests\Feature;

use App\Jobs\PolicyGenerateTripsBatchJob;
use App\Models\AcademicTerm;
use App\Models\Campus;
use App\Models\Driver;
use App\Models\P2pPolicyTerm;
use App\Models\PolicyGenerationRun;
use App\Models\PolicyRoute;
use App\Models\PolicyStudent;
use App\Models\PolicyTripSlot;
use App\Models\Student;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\P2pPolicy\P2pPolicyBatchProcessor;
use App\Services\P2pPolicy\P2pPolicyTripMaterializer;
use App\Support\P2pPolicy;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class P2pPolicyActivationTest extends TestCase
{
    use RefreshDatabase;

    private function seedPolicyFixture(): array
    {
        $this->seed(RbacSeeder::class);

        $dispatcher = User::factory()->create(['is_active' => true]);
        $dispatcher->assignRole('dispatcher');

        $origin = Campus::create(['code' => 'A', 'name' => 'Campus A', 'is_active' => true]);
        $dest = Campus::create(['code' => 'B', 'name' => 'Campus B', 'is_active' => true]);

        $academicTerm = AcademicTerm::create([
            'academic_year' => '2025-2026',
            'term_code' => 'HK1',
            'name' => 'Học kỳ 1',
            'starts_on' => '2025-08-01',
            'ends_on' => '2026-01-15',
            'is_active' => true,
        ]);

        $term = P2pPolicyTerm::create([
            'academic_term_id' => $academicTerm->id,
            'operating_from' => '2025-08-11',
            'operating_to' => '2025-08-13',
            'weekdays_mask' => 31,
            'status' => 'draft',
        ]);

        $vehicle = Vehicle::create(['license_plate' => '51A-TEST', 'status' => 'ready']);
        $driver = Driver::create(['full_name' => 'TX Test', 'employment_status' => 'active']);

        $route = PolicyRoute::create([
            'p2p_policy_term_id' => $term->id,
            'name' => 'Route 1',
            'origin_campus_id' => $origin->id,
            'dest_campus_id' => $dest->id,
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'is_active' => true,
        ]);

        $student = Student::create(['student_code' => 'HS001', 'full_name' => 'Nguyen A', 'grade' => '6A']);

        PolicyStudent::create([
            'policy_route_id' => $route->id,
            'student_id' => $student->id,
            'student_code' => 'HS001',
            'student_name' => 'Nguyen A',
            'class_name' => '6A',
            'direction' => 'one_way',
            'policy_type' => 'internal',
            'effective_from' => '2025-08-01',
            'is_active' => true,
        ]);

        return compact('dispatcher', 'term', 'route', 'vehicle', 'driver');
    }

    public function test_activate_dispatches_batch_job_and_is_idempotent_on_slots(): void
    {
        $fx = $this->seedPolicyFixture();
        $this->actingAs($fx['dispatcher']);

        Queue::fake();

        $first = $this->postJson("/api/p2p-policy/terms/{$fx['term']->id}/activate", [
            'confirm' => true,
        ], ['Idempotency-Key' => 'test-activate-1']);
        $first->assertOk();

        Queue::assertPushed(PolicyGenerateTripsBatchJob::class);

        $runId = (int) $first->json('data.generation_run.id');
        $this->assertGreaterThan(0, $runId);

        $run = PolicyGenerationRun::findOrFail($runId);
        $processor = app(P2pPolicyBatchProcessor::class);
        while ($processor->processNextBatch($run)) {
            $run->refresh();
        }

        $slots = PolicyTripSlot::query()->where('p2p_policy_term_id', $fx['term']->id)->count();
        $this->assertGreaterThan(0, $slots);

        $materializer = app(P2pPolicyTripMaterializer::class);
        $term = P2pPolicyTerm::findOrFail($fx['term']->id);
        $date = Carbon::parse('2025-08-11');
        $again = $materializer->materializeSlot($term, $fx['route'], $date, P2pPolicy::LEG_MORNING, $fx['dispatcher']->id);
        $this->assertSame('skipped', $again);
    }

    public function test_skips_weekend_when_not_in_mask(): void
    {
        $fx = $this->seedPolicyFixture();
        $fx['term']->update(['operating_from' => '2025-08-16', 'operating_to' => '2025-08-16', 'weekdays_mask' => 31]);

        $materializer = app(P2pPolicyTripMaterializer::class);
        $term = P2pPolicyTerm::findOrFail($fx['term']->id);
        $sat = Carbon::parse('2025-08-16');
        $this->assertSame(6, (int) $sat->isoWeekday());

        $result = $materializer->materializeSlot($term, $fx['route'], $sat, P2pPolicy::LEG_MORNING, $fx['dispatcher']->id);
        $this->assertSame('skipped', $result);
    }

    public function test_one_way_student_only_morning_leg(): void
    {
        $fx = $this->seedPolicyFixture();
        $fx['term']->update(['operating_from' => '2025-08-11', 'operating_to' => '2025-08-11']);

        $materializer = app(P2pPolicyTripMaterializer::class);
        $term = P2pPolicyTerm::findOrFail($fx['term']->id);
        $date = Carbon::parse('2025-08-11');

        $morning = $materializer->materializeSlot($term, $fx['route'], $date, P2pPolicy::LEG_MORNING, $fx['dispatcher']->id);
        $afternoon = $materializer->materializeSlot($term, $fx['route'], $date, P2pPolicy::LEG_AFTERNOON, $fx['dispatcher']->id);

        $this->assertSame('created', $morning);
        $this->assertSame('skipped', $afternoon);
    }

    public function test_skips_fixed_holiday_when_enabled(): void
    {
        $fx = $this->seedPolicyFixture();
        $fx['term']->update([
            'operating_from' => '2025-01-01',
            'operating_to' => '2025-01-01',
            'exclude_fixed_holidays' => true,
        ]);

        $materializer = app(P2pPolicyTripMaterializer::class);
        $term = P2pPolicyTerm::findOrFail($fx['term']->id);
        $date = Carbon::parse('2025-01-01');

        $result = $materializer->materializeSlot($term, $fx['route'], $date, P2pPolicy::LEG_MORNING, $fx['dispatcher']->id);
        $this->assertSame('skipped', $result);
    }

    public function test_skips_designated_skip_date(): void
    {
        $fx = $this->seedPolicyFixture();
        $fx['term']->update(['operating_from' => '2025-08-12', 'operating_to' => '2025-08-12']);

        \App\Models\PolicyTermSkipDate::create([
            'p2p_policy_term_id' => $fx['term']->id,
            'skip_date' => '2025-08-12',
            'reason' => 'Test skip',
        ]);

        $materializer = app(P2pPolicyTripMaterializer::class);
        $term = P2pPolicyTerm::findOrFail($fx['term']->id);
        $term->load('skipDates');
        $date = Carbon::parse('2025-08-12');

        $result = $materializer->materializeSlot($term, $fx['route'], $date, P2pPolicy::LEG_MORNING, $fx['dispatcher']->id);
        $this->assertSame('skipped', $result);
    }

    public function test_rbac_denies_view_without_permission(): void
    {
        $this->seed(RbacSeeder::class);
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('internal_user');

        $this->actingAs($user);
        $this->getJson('/api/p2p-policy/terms')->assertForbidden();
    }
}
