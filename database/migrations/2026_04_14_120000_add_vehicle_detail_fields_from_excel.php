<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bổ sung trường khớp cột sheet "Thông tin xe" (Phiếu đề xuất ghi nhận).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('owner_name')->nullable()->after('license_plate');
            $table->text('frame_engine_number')->nullable()->after('owner_name');
            $table->unsignedSmallInteger('year_manufactured')->nullable()->after('type');
            $table->date('purchased_at')->nullable()->after('year_manufactured');
            $table->unsignedSmallInteger('usage_expires_year')->nullable()->after('purchased_at');
            $table->string('insurance_provider', 64)->nullable()->after('payload_kg');
            $table->text('insurance_policy_note')->nullable()->after('insurance_provider');
            $table->date('road_fee_expires_at')->nullable()->after('insurance_expires_at');
            $table->string('registration_cycle_note')->nullable()->after('road_fee_expires_at');
            $table->date('last_maintenance_at')->nullable()->after('registration_cycle_note');
            $table->text('maintenance_schedule_note')->nullable()->after('last_maintenance_at');
            $table->string('caretaker_name')->nullable()->after('maintenance_schedule_note');
            $table->string('caretaker_phone', 32)->nullable()->after('caretaker_name');
            $table->text('notes')->nullable()->after('caretaker_phone');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn([
                'owner_name',
                'frame_engine_number',
                'year_manufactured',
                'purchased_at',
                'usage_expires_year',
                'insurance_provider',
                'insurance_policy_note',
                'road_fee_expires_at',
                'registration_cycle_note',
                'last_maintenance_at',
                'maintenance_schedule_note',
                'caretaker_name',
                'caretaker_phone',
                'notes',
            ]);
        });
    }
};
