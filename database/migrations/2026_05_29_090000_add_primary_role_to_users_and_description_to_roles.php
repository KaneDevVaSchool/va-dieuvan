<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('primary_role_name', 100)->nullable()->after('employee_code');
            $table->unsignedBigInteger('primary_role_id')->nullable()->after('primary_role_name');
            $table->foreign('primary_role_id')->references('id')->on('roles')->nullOnDelete();
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->text('description')->nullable()->after('display_name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['primary_role_id']);
            $table->dropColumn(['primary_role_name', 'primary_role_id']);
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
