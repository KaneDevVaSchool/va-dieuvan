<?php

namespace Tests\Feature;

use App\Models\AcademicTerm;
use App\Models\Campus;
use App\Models\P2pPolicyTerm;
use App\Models\PolicyRoute;
use App\Models\PolicyStudent;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class P2pPolicyStudentsBulkTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{user: User, route: PolicyRoute, route2: PolicyRoute, students: list<PolicyStudent>}
     */
    private function seedStudentsFixture(): array
    {
        $this->seed(RbacSeeder::class);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('dispatcher');

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
            'operating_from' => '2025-08-01',
            'operating_to' => '2026-01-01',
            'status' => 'draft',
        ]);

        $route = PolicyRoute::create([
            'p2p_policy_term_id' => $term->id,
            'name' => 'Tuyen A',
            'origin_campus_id' => $origin->id,
            'dest_campus_id' => $dest->id,
            'is_active' => true,
        ]);

        $route2 = PolicyRoute::create([
            'p2p_policy_term_id' => $term->id,
            'name' => 'Tuyen B',
            'origin_campus_id' => $origin->id,
            'dest_campus_id' => $dest->id,
            'is_active' => true,
        ]);

        $students = [];
        foreach (['HS001', 'HS002'] as $code) {
            $student = Student::create(['student_code' => $code, 'full_name' => "Name {$code}", 'grade' => '6A']);
            $students[] = PolicyStudent::create([
                'policy_route_id' => $route->id,
                'student_id' => $student->id,
                'student_code' => $code,
                'student_name' => "Name {$code}",
                'class_name' => '6A',
                'direction' => 'two_way',
                'policy_type' => 'internal',
                'effective_from' => '2025-08-01',
                'is_active' => true,
            ]);
        }

        return ['user' => $user, 'route' => $route, 'route2' => $route2, 'students' => $students];
    }

    public function test_bulk_delete_removes_policy_students(): void
    {
        $fx = $this->seedStudentsFixture();
        $ids = collect($fx['students'])->pluck('id')->all();

        $this->actingAs($fx['user'])
            ->postJson('/api/p2p-policy/students/bulk-delete', ['ids' => $ids])
            ->assertOk()
            ->assertJsonPath('data.deleted_count', 2);

        $this->assertDatabaseCount('policy_students', 0);
    }

    public function test_bulk_assign_updates_route(): void
    {
        $fx = $this->seedStudentsFixture();
        $ids = collect($fx['students'])->pluck('id')->all();

        $this->actingAs($fx['user'])
            ->postJson('/api/p2p-policy/students/bulk-assign', [
                'ids' => $ids,
                'policy_route_id' => $fx['route2']->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.updated_count', 2);

        foreach ($fx['students'] as $row) {
            $this->assertDatabaseHas('policy_students', [
                'id' => $row->id,
                'policy_route_id' => $fx['route2']->id,
            ]);
        }
    }

    public function test_bulk_delete_rejects_invalid_ids(): void
    {
        $fx = $this->seedStudentsFixture();

        $this->actingAs($fx['user'])
            ->postJson('/api/p2p-policy/students/bulk-delete', ['ids' => [99999]])
            ->assertStatus(422);
    }

    public function test_bulk_delete_forbidden_without_manage_permission(): void
    {
        $this->seed(RbacSeeder::class);
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('accountant');

        $this->actingAs($user)
            ->postJson('/api/p2p-policy/students/bulk-delete', ['ids' => [1]])
            ->assertForbidden();
    }
}
