<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['hr_companies', 'hr_branches', 'hr_levels', 'hr_job_titles'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('departments', function (Blueprint $table) {
            $table->foreignId('hr_company_id')->nullable()->constrained('hr_companies')->nullOnDelete();
            $table->string('manager_email')->nullable();
            $table->foreignId('manager_user_id')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::create('hr_divisions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->string('manager_email')->nullable();
            $table->foreignId('manager_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('hr_employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_code')->unique();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('company_email')->nullable();
            $table->string('phone')->nullable();
            $table->string('employment_status')->nullable();
            $table->foreignId('hr_company_id')->nullable()->constrained('hr_companies')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('hr_division_id')->nullable()->constrained('hr_divisions')->nullOnDelete();
            $table->foreignId('hr_branch_id')->nullable()->constrained('hr_branches')->nullOnDelete();
            $table->foreignId('hr_level_id')->nullable()->constrained('hr_levels')->nullOnDelete();
            $table->foreignId('hr_job_title_id')->nullable()->constrained('hr_job_titles')->nullOnDelete();
            // Preserve unresolved organization references, without copying identity/bank data.
            $table->json('organization_labels')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_employees');
        Schema::dropIfExists('hr_divisions');
        Schema::table('departments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('manager_user_id');
            $table->dropConstrainedForeignId('hr_company_id');
            $table->dropColumn('manager_email');
        });
        foreach (['hr_job_titles', 'hr_levels', 'hr_branches', 'hr_companies'] as $name) {
            Schema::dropIfExists($name);
        }
    }
};
