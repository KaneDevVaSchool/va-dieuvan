<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('p2p_policy_terms', function (Blueprint $table) {
            $table->string('name', 200)->nullable()->after('academic_term_id');
        });
    }

    public function down(): void
    {
        Schema::table('p2p_policy_terms', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }
};
