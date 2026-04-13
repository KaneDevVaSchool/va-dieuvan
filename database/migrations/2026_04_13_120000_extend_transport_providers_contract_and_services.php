<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transport_providers', function (Blueprint $table) {
            $table->string('contact_email')->nullable()->after('contact_phone');
            $table->string('contract_number')->nullable()->after('notes');
            $table->date('contract_signed_at')->nullable()->after('contract_number');
            $table->date('contract_expires_at')->nullable()->after('contract_signed_at');
            $table->json('services')->nullable()->after('contract_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('transport_providers', function (Blueprint $table) {
            $table->dropColumn([
                'contact_email',
                'contract_number',
                'contract_signed_at',
                'contract_expires_at',
                'services',
            ]);
        });
    }
};
