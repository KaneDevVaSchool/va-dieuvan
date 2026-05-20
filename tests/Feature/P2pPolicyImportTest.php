<?php

namespace Tests\Feature;

use App\Models\AcademicTerm;
use App\Models\AuditLog;
use App\Models\Campus;
use App\Models\P2pPolicyTerm;
use App\Models\PolicyRoute;
use App\Models\PolicyStudent;
use App\Models\Student;
use App\Models\User;
use App\Services\P2pPolicy\PolicyStudentSpreadsheetSpec;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;

class P2pPolicyImportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{user: User, term: P2pPolicyTerm, route: PolicyRoute}
     */
    private function seedImportFixture(): array
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

        return ['user' => $user, 'term' => $term, 'route' => $route];
    }

    /**
     * @param  list<list<string>>  $dataRows
     */
    private function makeSpreadsheet(array $dataRows): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'p2p-xlsx-').'.xlsx';
        $writer = new Writer;
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues(PolicyStudentSpreadsheetSpec::LABELS_VI));
        $writer->addRow(Row::fromValues(PolicyStudentSpreadsheetSpec::KEYS));
        foreach ($dataRows as $row) {
            $writer->addRow(Row::fromValues($row));
        }
        $writer->close();

        return new UploadedFile($path, 'roster.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    public function test_import_requires_permission(): void
    {
        $this->seed(RbacSeeder::class);
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('internal_user');

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

        $this->actingAs($user);
        $file = UploadedFile::fake()->create('roster.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->postJson('/api/p2p-policy/students/import/preview', [
            'file' => $file,
            'p2p_policy_term_id' => $term->id,
        ])->assertForbidden();
    }

    public function test_preview_and_commit_create_student_with_audit(): void
    {
        ['user' => $user, 'term' => $term] = $this->seedImportFixture();

        $file = $this->makeSpreadsheet([
            ['Tuyen A', 'HS100', 'Tran B', '7A', 'two_way', 'default', '', '', '2025-09-01', '', '1', ''],
            ['Tuyen Sai', 'HS101', 'Le C', '7B', 'two_way', 'default', '', '', '2025-09-01', '', '1', ''],
        ]);

        $this->actingAs($user);

        $preview = $this->postJson('/api/p2p-policy/students/import/preview', [
            'file' => $file,
            'p2p_policy_term_id' => $term->id,
        ])->assertOk()->json('data');

        $this->assertNotEmpty($preview['preview_id']);
        $this->assertSame(1, $preview['summary']['create']);
        $this->assertSame(1, $preview['summary']['error']);

        $commit = $this->postJson('/api/p2p-policy/students/import/commit', [
            'preview_id' => $preview['preview_id'],
        ])->assertOk()->json('data');

        $this->assertSame(1, $commit['imported']);
        $this->assertSame(0, $commit['updated']);

        $this->assertDatabaseHas('policy_students', ['student_code' => 'HS100']);
        $this->assertTrue(
            AuditLog::query()->where('event', 'p2p_policy.student.import_create')->exists()
        );
        $this->assertTrue(
            AuditLog::query()->where('event', 'p2p_policy.students.import')->exists()
        );
    }

    public function test_preview_detects_update(): void
    {
        ['user' => $user, 'term' => $term, 'route' => $route] = $this->seedImportFixture();

        $student = Student::create(['student_code' => 'HS200', 'full_name' => 'Old Name', 'grade' => '8A', 'is_active' => true]);
        PolicyStudent::create([
            'policy_route_id' => $route->id,
            'student_id' => $student->id,
            'student_code' => 'HS200',
            'student_name' => 'Old Name',
            'class_name' => '8A',
            'direction' => 'two_way',
            'policy_type' => 'default',
            'effective_from' => '2025-09-01',
            'is_active' => true,
        ]);

        $file = $this->makeSpreadsheet([
            ['Tuyen A', 'HS200', 'New Name', '8B', 'two_way', 'default', '', '', '2025-09-01', '', '1', 'note'],
        ]);

        $this->actingAs($user);

        $preview = $this->postJson('/api/p2p-policy/students/import/preview', [
            'file' => $file,
            'p2p_policy_term_id' => $term->id,
        ])->assertOk()->json('data');

        $this->assertSame(1, $preview['summary']['update']);
        $this->assertSame(0, $preview['summary']['create']);

        $this->postJson('/api/p2p-policy/students/import/commit', [
            'preview_id' => $preview['preview_id'],
        ])->assertOk();

        $this->assertDatabaseHas('policy_students', [
            'student_code' => 'HS200',
            'student_name' => 'New Name',
            'class_name' => '8B',
        ]);
        $this->assertTrue(
            AuditLog::query()->where('event', 'p2p_policy.student.import_update')->exists()
        );
    }
}
