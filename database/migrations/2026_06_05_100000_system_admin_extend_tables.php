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

        Schema::table($rolesTable, function (Blueprint $table) use ($rolesTable) {
            if (! Schema::hasColumn($rolesTable, 'color')) {
                $table->string('color', 20)->nullable()->after('display_name');
            }
            if (! Schema::hasColumn($rolesTable, 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('color');
            }
            if (! Schema::hasColumn($rolesTable, 'status')) {
                $table->string('status', 20)->default('active')->after('sort_order');
            }
            if (! Schema::hasColumn($rolesTable, 'is_system')) {
                $table->boolean('is_system')->default(false)->after('status');
            }
            if (! Schema::hasColumn($rolesTable, 'parent_id')) {
                $table->unsignedBigInteger('parent_id')->nullable()->after('is_system');
                $table->foreign('parent_id')->references('id')->on($rolesTable)->nullOnDelete();
            }
            if (! Schema::hasColumn($rolesTable, 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn($rolesTable, 'updated_by')) {
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn($rolesTable, 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table($permissionsTable, function (Blueprint $table) use ($permissionsTable) {
            if (! Schema::hasColumn($permissionsTable, 'module')) {
                $table->string('module', 60)->nullable()->after('plain_description');
            }
            if (! Schema::hasColumn($permissionsTable, 'action_type')) {
                $table->string('action_type', 20)->nullable()->after('module');
            }
            if (! Schema::hasColumn($permissionsTable, 'action')) {
                $table->string('action', 40)->nullable()->after('action_type');
            }
            if (! Schema::hasColumn($permissionsTable, 'is_sensitive')) {
                $table->boolean('is_sensitive')->default(false)->after('action');
            }
            if (! Schema::hasColumn($permissionsTable, 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('is_sensitive');
            }
        });

        if (! Schema::hasTable('role_assignments')) {
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
        }

        if (! Schema::hasTable('menu_items')) {
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
        }

        if (! Schema::hasTable('user_table_prefs')) {
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

        Schema::table('audit_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('audit_logs', 'actor_name')) {
                $table->string('actor_name')->nullable()->after('actor_id');
            }
            if (! Schema::hasColumn('audit_logs', 'module')) {
                $table->string('module', 60)->nullable()->after('event');
            }
            if (! Schema::hasColumn('audit_logs', 'action')) {
                $table->string('action', 40)->nullable()->after('module');
            }
            if (! Schema::hasColumn('audit_logs', 'result')) {
                $table->string('result', 20)->default('success')->after('after');
            }
            if (! Schema::hasColumn('audit_logs', 'ip_address')) {
                $table->string('ip_address', 45)->nullable()->after('result');
            }
            if (! Schema::hasColumn('audit_logs', 'user_agent')) {
                $table->string('user_agent', 512)->nullable()->after('ip_address');
            }
            if (! Schema::hasColumn('audit_logs', 'device')) {
                $table->string('device', 80)->nullable()->after('user_agent');
            }
            if (! Schema::hasColumn('audit_logs', 'browser')) {
                $table->string('browser', 80)->nullable()->after('device');
            }
            if (! Schema::hasColumn('audit_logs', 'os')) {
                $table->string('os', 80)->nullable()->after('browser');
            }
            $table->index(['module', 'created_at']);
            $table->index('ip_address');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_table_prefs');
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('role_assignments');

        $tableNames = config('permission.table_names');
        $rolesTable = $tableNames['roles'] ?? 'roles';
        $permissionsTable = $tableNames['permissions'] ?? 'permissions';

        Schema::table('audit_logs', function (Blueprint $table) {
            if (Schema::hasColumn('audit_logs', 'module')) {
                $table->dropIndex(['module', 'created_at']);
            }
            if (Schema::hasColumn('audit_logs', 'ip_address')) {
                $table->dropIndex(['ip_address']);
            }
            $cols = ['actor_name', 'module', 'action', 'result', 'ip_address', 'user_agent', 'device', 'browser', 'os'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('audit_logs', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table($rolesTable, function (Blueprint $table) use ($rolesTable) {
            if (Schema::hasColumn($rolesTable, 'deleted_at')) {
                $table->dropSoftDeletes();
            }
            foreach (['parent_id', 'created_by', 'updated_by'] as $col) {
                if (Schema::hasColumn($rolesTable, $col)) {
                    $table->dropForeign([$col]);
                }
            }
            foreach (['parent_id', 'created_by', 'updated_by', 'is_system', 'status', 'sort_order', 'color'] as $col) {
                if (Schema::hasColumn($rolesTable, $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table($permissionsTable, function (Blueprint $table) use ($permissionsTable) {
            foreach (['module', 'action_type', 'action', 'is_sensitive', 'sort_order'] as $col) {
                if (Schema::hasColumn($permissionsTable, $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
