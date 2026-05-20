<?php

namespace Tests\Feature;

use App\Models\AcademicTerm;
use App\Models\P2pPolicyTerm;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class P2pPolicyImportTest extends TestCase
{
    use RefreshDatabase;

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

        $this->postJson('/api/p2p-policy/students/import', [
            'file' => $file,
            'p2p_policy_term_id' => $term->id,
        ])->assertForbidden();
    }
}
