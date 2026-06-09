<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $rolesTable = $tableNames['roles'] ?? 'roles';
        $permissionsTable = $tableNames['permissions'] ?? 'permissions';

        Schema::create('role_assignments', function (Blueprint $table) use ($rolesTable) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained($rolesTable)->cascadeOnDelete();
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->string('status', 20)->default('active');
            $table->boolean('is_temporary')->default(false);
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'effective_to']);
        });

        Schema::create('menu_items', function (Blueprint $table) use ($permissionsTable) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->nullOnDelete();
            $table->string('type', 20)->default('item');
            $table->string('label', 120);
            $table->string('label_key', 120)->nullable();
            $table->string('icon', 60)->nullable();
            $table->string('route_name', 120)->nullable();
            $table->string('url', 255)->nullable();
            $table->string('target', 10)->default('_self');
            $table->string('badge_key', 60)->nullable();
            $table->foreignId('permission_id')->nullable()->constrained($permissionsTable)->nullOnDelete();
            $table->string('feature_key', 80)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['parent_id', 'sort_order']);
            $table->index('is_visible');
        });

        Schema::create('user_table_prefs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('table_key', 80);
            $table->json('columns')->nullable();
            $table->json('saved_filters')->nullable();
            $table->string('default_filter_id')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'table_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_table_prefs');
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('role_assignments');
    }
};
